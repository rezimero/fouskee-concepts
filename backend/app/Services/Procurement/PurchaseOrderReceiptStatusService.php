<?php

namespace App\Services\Procurement;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Services\Support\DecimalMath;

class PurchaseOrderReceiptStatusService
{
    public function __construct(
        private readonly ProcurementReceiptTotals $totals,
        private readonly DecimalMath $math,
    ) {}

    public function refresh(PurchaseOrder $order): PurchaseOrder
    {
        $order->loadMissing('lines');

        $hasReceipt = false;
        $fullyReceived = true;

        foreach ($order->lines as $line) {
            $received = $this->totals->postedBase($line);

            if ($this->math->isPositive($received)) {
                $hasReceipt = true;
            }

            if (! $this->math->greaterThanOrEqual(
                $received,
                $line->ordered_quantity_base,
            )) {
                $fullyReceived = false;
            }
        }

        $status = $fullyReceived
            ? PurchaseOrderStatus::Received
            : ($hasReceipt
                ? PurchaseOrderStatus::PartiallyReceived
                : PurchaseOrderStatus::Approved);

        $order->update(['status' => $status]);

        return $order->refresh();
    }
}
