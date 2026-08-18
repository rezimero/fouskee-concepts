<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcurementMatchRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'run_number' => $this->run_number,
            'result' => $this->result->value,
            'supplier_invoice_id' => $this->invoice?->public_id,
            'expected_amount' => $this->expected_amount,
            'invoiced_amount' => $this->invoiced_amount,
            'amount_variance' => $this->amount_variance,
            'matched_at' => $this->matched_at?->toISOString(),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(
                fn ($line) => [
                    'invoice_line_id' => $line->invoiceLine?->public_id,
                    'purchase_order_line_id' => $line->invoiceLine?->purchaseOrderLine?->public_id,
                    'result' => $line->result->value,
                    'ordered_quantity_base' => $line->ordered_quantity_base,
                    'received_quantity_base' => $line->received_quantity_base,
                    'invoiced_quantity_base' => $line->invoiced_quantity_base,
                    'cumulative_invoiced_quantity_base' => $line->cumulative_invoiced_quantity_base,
                    'ordered_unit_price_base' => $line->ordered_unit_price_base,
                    'invoiced_unit_price_base' => $line->invoiced_unit_price_base,
                    'expected_amount' => $line->expected_amount,
                    'invoiced_amount' => $line->invoiced_amount,
                    'amount_variance' => $line->amount_variance,
                    'exception_codes' => $line->exception_codes ?? [],
                ],
            )),
        ];
    }
}
