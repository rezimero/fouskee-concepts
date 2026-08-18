<?php

namespace App\Services\Procurement;

use App\Enums\ProcurementMatchResult;
use App\Enums\SupplierInvoiceStatus;
use App\Services\Support\DecimalMath;

class ThreeWayMatchResultResolver
{
    public function __construct(
        private readonly ProcurementTolerance $tolerance,
        private readonly DecimalMath $math,
    ) {}

    public function overall(
        array $evaluated,
        string $expected,
        string $actual,
        string $amountTolerance,
    ): ProcurementMatchResult {
        foreach ($evaluated as [, $result]) {
            if ($result['result'] === ProcurementMatchResult::Exception) {
                return ProcurementMatchResult::Exception;
            }
        }

        if ($this->tolerance->exceedsAbsolute(
            $actual,
            $expected,
            $amountTolerance,
        )) {
            return ProcurementMatchResult::Exception;
        }

        $hasVariance = ! $this->math->isZero(
            $this->math->subtract($actual, $expected),
        );

        foreach ($evaluated as [, $result]) {
            $hasVariance = $hasVariance
                || $result['result'] === ProcurementMatchResult::WithinTolerance;
        }

        return $hasVariance
            ? ProcurementMatchResult::WithinTolerance
            : ProcurementMatchResult::Matched;
    }

    public function invoiceStatus(
        ProcurementMatchResult $result,
    ): SupplierInvoiceStatus {
        return match ($result) {
            ProcurementMatchResult::Matched => SupplierInvoiceStatus::Matched,
            ProcurementMatchResult::WithinTolerance => SupplierInvoiceStatus::WithinTolerance,
            ProcurementMatchResult::Exception => SupplierInvoiceStatus::Exception,
        };
    }
}
