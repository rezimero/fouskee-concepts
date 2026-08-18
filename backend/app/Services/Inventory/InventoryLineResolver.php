<?php

namespace App\Services\Inventory;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductPackaging;

class InventoryLineResolver
{
    public function __construct(
        private readonly InventoryQuantityMath $math,
    ) {}

    public function resolve(
        Organization $organization,
        array $line,
        bool $signed = false,
    ): array {
        $product = Product::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $line['product_id'])
            ->where('status', 'active')
            ->firstOrFail();

        $packaging = $this->packaging($product->id, $line['packaging_id'] ?? null);
        $conversion = $packaging?->conversion_to_base ?? '1.000000';
        $entered = (string) ($signed ? $line['quantity_delta'] : $line['quantity']);

        return [
            'product' => $product,
            'packaging' => $packaging,
            'entered_quantity' => $entered,
            'conversion_to_base_snapshot' => $conversion,
            'quantity_base' => $this->math->multiply($entered, $conversion),
            'unit_cost_snapshot' => isset($line['unit_cost_base'])
                ? (string) $line['unit_cost_base']
                : null,
        ];
    }

    private function packaging(int $productId, ?string $publicId): ?ProductPackaging
    {
        if ($publicId === null) {
            return null;
        }

        return ProductPackaging::query()
            ->where('product_id', $productId)
            ->where('public_id', $publicId)
            ->where('status', 'active')
            ->firstOrFail();
    }
}
