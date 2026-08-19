<?php

namespace App\Services\Sales;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductPackaging;

class SalesProductResolver
{
    public function product(Organization $organization, string $publicId): Product
    {
        return Product::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->where('status', 'active')
            ->firstOrFail();
    }

    public function packaging(Product $product, string $publicId): ProductPackaging
    {
        return ProductPackaging::query()
            ->where('product_id', $product->id)
            ->where('public_id', $publicId)
            ->where('status', 'active')
            ->firstOrFail();
    }
}
