<?php

namespace App\Services\Procurement;

use App\Exceptions\InventoryDomainException;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Services\Support\DecimalMath;

class GoodsReceiptLineFactory
{
    public function __construct(
        private readonly DecimalMath $math,
    ) {}

    public function make(
        PurchaseOrder $order,
        array $input,
    ): array {
        $line = PurchaseOrderLine::query()
            ->with(['product', 'packaging'])
            ->where('purchase_order_id', $order->id)
            ->where('public_id', $input['purchase_order_line_id'])
            ->firstOrFail();

        $quantity = (string) $input['quantity'];

        if (! $this->math->isPositive($quantity)) {
            throw new InventoryDomainException(
                'procurement_receipt_quantity_invalid',
                'Received quantity must be greater than zero.',
                422,
            );
        }

        return [
            'purchase_order_line' => $line,
            'purchase_order_line_id' => $line->id,
            'product_id' => $line->product_id,
            'product_packaging_id' => $line->product_packaging_id,
            'received_quantity' => $quantity,
            'conversion_to_base_snapshot' => $line->conversion_to_base_snapshot,
            'received_quantity_base' => $this->math->multiply(
                $quantity,
                $line->conversion_to_base_snapshot,
            ),
            'unit_cost_base_snapshot' => $line->unit_price_base,
            'lot_code' => $input['lot_code'] ?? null,
            'expiry_date' => $input['expiry_date'] ?? null,
        ];
    }
}
