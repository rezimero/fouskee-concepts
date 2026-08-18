<?php

namespace App\Services\Procurement;

use App\Enums\ProcurementMatchResult;
use App\Models\ProcurementPolicyVersion;
use App\Models\SupplierInvoiceLine;
use App\Services\Support\DecimalMath;

class ThreeWayMatchLineEvaluator
{
    public function __construct(
        private readonly ProcurementReceiptTotals $receipts,
        private readonly ProcurementInvoiceTotals $invoices,
        private readonly ProcurementTolerance $tolerance,
        private readonly DecimalMath $math,
    ) {}

    public function evaluate(
        SupplierInvoiceLine $invoiceLine,
        ProcurementPolicyVersion $policy,
        string $invoiceCurrency,
    ): array {
        $orderLine = $invoiceLine->purchaseOrderLine;
        $exceptions = [];

        if ($orderLine === null) {
            return $this->unlinked($invoiceLine);
        }

        $received = $this->receipts->postedBase($orderLine);
        $cumulative = $this->invoices->cumulativeBase($orderLine);
        $ordered = (string) $orderLine->ordered_quantity_base;
        $invoiceQty = (string) $invoiceLine->invoiced_quantity_base;
        $orderPrice = (string) $orderLine->unit_price_base;
        $invoicePrice = (string) $invoiceLine->unit_price_base;
        $expected = $this->math->multiply($invoiceQty, $orderPrice);
        $actual = (string) $invoiceLine->line_amount;

        if ($this->tolerance->exceedsUpper(
            $received,
            $ordered,
            $policy->over_receipt_tolerance_percent,
        )) {
            $exceptions[] = 'received_over_order';
        }

        if ($this->tolerance->exceedsUpper(
            $cumulative,
            $received,
            $policy->invoice_quantity_tolerance_percent,
        )) {
            $exceptions[] = 'invoiced_over_received';
        }

        if ($this->tolerance->exceedsAbsolute(
            $invoicePrice,
            $orderPrice,
            $policy->invoice_price_tolerance_percent,
        )) {
            $exceptions[] = 'price_variance';
        }

        if ($this->tolerance->exceedsAbsolute(
            $actual,
            $expected,
            $policy->invoice_amount_tolerance_percent,
        )) {
            $exceptions[] = 'amount_variance';
        }

        if ($orderLine->purchaseOrder->currency_code !== $invoiceCurrency) {
            $exceptions[] = 'currency_mismatch';
        }

        $hasVariance = ! $this->math->isZero(
            $this->math->subtract($invoicePrice, $orderPrice),
        ) || ! $this->math->isZero(
            $this->math->subtract($actual, $expected),
        );

        return [
            'purchase_order_line_id' => $orderLine->id,
            'result' => $exceptions !== []
                ? ProcurementMatchResult::Exception
                : ($hasVariance
                    ? ProcurementMatchResult::WithinTolerance
                    : ProcurementMatchResult::Matched),
            'ordered_quantity_base' => $ordered,
            'received_quantity_base' => $received,
            'invoiced_quantity_base' => $invoiceQty,
            'cumulative_invoiced_quantity_base' => $cumulative,
            'ordered_unit_price_base' => $orderPrice,
            'invoiced_unit_price_base' => $invoicePrice,
            'expected_amount' => $expected,
            'invoiced_amount' => $actual,
            'quantity_variance_base' => $this->math->subtract(
                $cumulative,
                $received,
            ),
            'price_variance_base' => $this->math->subtract(
                $invoicePrice,
                $orderPrice,
            ),
            'amount_variance' => $this->math->subtract($actual, $expected),
            'exception_codes' => $exceptions ?: null,
        ];
    }

    private function unlinked(SupplierInvoiceLine $line): array
    {
        return [
            'purchase_order_line_id' => null,
            'result' => ProcurementMatchResult::Exception,
            'ordered_quantity_base' => null,
            'received_quantity_base' => '0.000000',
            'invoiced_quantity_base' => $line->invoiced_quantity_base,
            'cumulative_invoiced_quantity_base' => $line->invoiced_quantity_base,
            'ordered_unit_price_base' => null,
            'invoiced_unit_price_base' => $line->unit_price_base,
            'expected_amount' => '0.000000',
            'invoiced_amount' => $line->line_amount,
            'quantity_variance_base' => $line->invoiced_quantity_base,
            'price_variance_base' => $line->unit_price_base,
            'amount_variance' => $line->line_amount,
            'exception_codes' => ['unlinked_invoice_line'],
        ];
    }
}
