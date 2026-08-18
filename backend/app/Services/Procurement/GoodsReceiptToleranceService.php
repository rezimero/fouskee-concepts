<?php

namespace App\Services\Procurement;

use App\Models\ProcurementPolicyVersion;
use App\Services\Support\DecimalMath;

class GoodsReceiptToleranceService
{
    public function __construct(
        private readonly ProcurementReceiptTotals $totals,
        private readonly DecimalMath $math,
    ) {}

    public function exceeds(array $lines, ProcurementPolicyVersion $policy): bool
    {
        foreach ($lines as $line) {
            $orderLine = $line['purchase_order_line'];
            $posted = $this->totals->postedBase($orderLine);
            $after = $this->math->add(
                $posted,
                $line['received_quantity_base'],
            );
            $allowed = $this->math->add(
                $orderLine->ordered_quantity_base,
                $this->math->percentOf(
                    $orderLine->ordered_quantity_base,
                    $policy->over_receipt_tolerance_percent,
                ),
            );

            if ($this->math->greaterThan($after, $allowed)) {
                return true;
            }
        }

        return false;
    }
}
