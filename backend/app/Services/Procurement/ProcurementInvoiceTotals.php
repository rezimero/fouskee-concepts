<?php

namespace App\Services\Procurement;

use App\Models\PurchaseOrderLine;
use App\Models\SupplierInvoiceLine;

class ProcurementInvoiceTotals
{
    public function cumulativeBase(PurchaseOrderLine $line): string
    {
        return (string) SupplierInvoiceLine::query()
            ->where('purchase_order_line_id', $line->id)
            ->sum('invoiced_quantity_base');
    }
}
