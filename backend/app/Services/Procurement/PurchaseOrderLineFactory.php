<?php

namespace App\Services\Procurement;

use App\Models\Organization;
use App\Services\Support\DecimalMath;

class PurchaseOrderLineFactory
{
    public function __construct(
        private readonly ProcurementProductResolver $products,
        private readonly DecimalMath $math,
    ) {}

    public function make(
        Organization $organization,
        array $input,
        int $lineNumber,
    ): array {
        $product = $this->products->product(
            $organization,
            $input['product_id'],
        );
        $packaging = $this->products->packaging(
            $product,
            $input['packaging_id'] ?? null,
        );

        $conversion = (string) ($packaging?->conversion_to_base ?? 1);
        $quantity = (string) $input['quantity'];
        $unitPrice = (string) $input['unit_price'];

        return [
            'line_number' => $lineNumber,
            'product_id' => $product->id,
            'product_packaging_id' => $packaging?->id,
            'product_sku_snapshot' => $product->sku,
            'product_name_snapshot' => $product->name,
            'packaging_name_snapshot' => $packaging?->name,
            'conversion_to_base_snapshot' => $conversion,
            'ordered_quantity' => $quantity,
            'ordered_quantity_base' => $this->math->multiply(
                $quantity,
                $conversion,
            ),
            'unit_price' => $unitPrice,
            'unit_price_base' => $this->math->divide(
                $unitPrice,
                $conversion,
            ),
            'line_total' => $this->math->multiply($quantity, $unitPrice),
        ];
    }
}
