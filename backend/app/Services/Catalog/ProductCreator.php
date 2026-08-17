<?php

namespace App\Services\Catalog;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class ProductCreator
{
    public function __construct(
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        array $data,
    ): Product {
        return DB::transaction(function () use ($organization, $actor, $data): Product {
            $baseUnit = $this->unit($organization, $data['base_unit_id']);

            $product = Product::query()->create([
                'organization_id' => $organization->id,
                'sku' => $data['sku'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'brand_id' => $this->brandId($organization, $data['brand_id'] ?? null),
                'product_category_id' => $this->categoryId(
                    $organization,
                    $data['category_id'] ?? null,
                ),
                'base_unit_id' => $baseUnit->id,
                'status' => ProductStatus::Active,
            ]);

            foreach ($data['packagings'] as $packaging) {
                $unit = $this->unit($organization, $packaging['unit_id']);

                $product->packagings()->create([
                    'unit_of_measure_id' => $unit->id,
                    'code' => $packaging['code'],
                    'name' => $packaging['name'],
                    'barcode' => $packaging['barcode'] ?? null,
                    'conversion_to_base' => $packaging['conversion_to_base'],
                    'is_default_sale' => $packaging['is_default_sale'] ?? false,
                    'is_default_purchase' => $packaging['is_default_purchase'] ?? false,
                    'status' => 'active',
                ]);
            }

            $this->audit->record(
                event: 'catalog.product.created',
                actor: $actor,
                subject: $product,
                organizationId: $organization->id,
                after: [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'packaging_count' => count($data['packagings']),
                ],
            );

            return $product->load(['brand', 'category', 'baseUnit', 'packagings.unit']);
        });
    }

    private function unit(Organization $organization, string $publicId): UnitOfMeasure
    {
        return UnitOfMeasure::query()
            ->where('public_id', $publicId)
            ->where(fn ($query) => $query
                ->whereNull('organization_id')
                ->orWhere('organization_id', $organization->id))
            ->firstOrFail();
    }

    private function brandId(Organization $organization, ?string $publicId): ?int
    {
        return $this->scopedId(Brand::class, $organization, $publicId);
    }

    private function categoryId(Organization $organization, ?string $publicId): ?int
    {
        return $this->scopedId(ProductCategory::class, $organization, $publicId);
    }

    private function scopedId(
        string $model,
        Organization $organization,
        ?string $publicId,
    ): ?int {
        if ($publicId === null) {
            return null;
        }

        return $model::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->firstOrFail()
            ->getKey();
    }
}
