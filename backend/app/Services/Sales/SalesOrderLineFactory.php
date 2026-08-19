<?php

namespace App\Services\Sales;

use App\Models\Organization;
use App\Models\PriceList;
use App\Services\Support\DecimalMath;

class SalesOrderLineFactory
{
    public function __construct(
        private readonly SalesProductResolver $products,
        private readonly SalesPriceResolver $prices,
        private readonly DecimalMath $math,
    ) {}

    public function make(
        Organization $organization,
        PriceList $priceList,
        array $input,
        int $lineNumber,
    ): array {
        $product = $this->products->product(
            $organization,
            $input['product_id'],
        );
        $packaging = $this->products->packaging(
            $product,
            $input['packaging_id'],
        );
        $price = $this->prices->resolve(
            $priceList,
            $packaging,
            $input['requested_unit_price'] ?? null,
        );
        $quantity = (string) $input['quantity'];
        $conversion = (string) $packaging->conversion_to_base;

        return [
            'line_number' => $lineNumber,
            'product_id' => $product->id,
            'product_packaging_id' => $packaging->id,
            'product_sku_snapshot' => $product->sku,
            'product_name_snapshot' => $product->name,
            'packaging_name_snapshot' => $packaging->name,
            'conversion_to_base_snapshot' => $conversion,
            'ordered_quantity' => $quantity,
            'ordered_quantity_base' => $this->math->multiply(
                $quantity,
                $conversion,
            ),
            ...$price,
            'line_total' => $this->math->multiply(
                $quantity,
                $price['effective_unit_price'],
            ),
        ];
    }
}
