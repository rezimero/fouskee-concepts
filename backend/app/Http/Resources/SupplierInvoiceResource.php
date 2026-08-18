<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date?->toDateString(),
            'status' => $this->status->value,
            'eligible_for_payment' => $this->status->eligibleForPayment(),
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->public_id,
                'name' => $this->supplier->display_name,
            ]),
            'currency_code' => $this->currency_code,
            'subtotal_amount' => $this->subtotal_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'latest_match_run_id' => $this->latestMatchRun?->public_id,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(
                fn ($line) => [
                    'id' => $line->public_id,
                    'purchase_order_line_id' => $line->purchaseOrderLine?->public_id,
                    'product_id' => $line->product?->public_id,
                    'sku' => $line->product_sku_snapshot,
                    'product_name' => $line->product_name_snapshot,
                    'quantity' => $line->invoiced_quantity,
                    'quantity_base' => $line->invoiced_quantity_base,
                    'unit_price' => $line->unit_price,
                    'unit_price_base' => $line->unit_price_base,
                    'line_amount' => $line->line_amount,
                ],
            )),
        ];
    }
}
