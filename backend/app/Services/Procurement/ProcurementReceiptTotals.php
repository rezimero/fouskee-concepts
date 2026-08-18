<?php

namespace App\Services\Procurement;

use App\Models\GoodsReceiptLine;
use App\Models\PurchaseOrderLine;

class ProcurementReceiptTotals
{
    public function postedBase(PurchaseOrderLine $line): string
    {
        return (string) GoodsReceiptLine::query()
            ->where('purchase_order_line_id', $line->id)
            ->whereHas(
                'goodsReceipt',
                fn ($query) => $query->where('status', 'posted'),
            )
            ->sum('received_quantity_base');
    }
}
