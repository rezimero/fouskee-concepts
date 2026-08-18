<?php

namespace Tests\Feature\Commercial;

use App\Models\UnitOfMeasure;
use Tests\Support\InventoryTestCase;

class ProductPackagingDefaultTest extends InventoryTestCase
{
    public function test_product_rejects_multiple_default_sale_packagings(): void
    {
        $kg = UnitOfMeasure::query()->where('code', 'KG')->firstOrFail();
        $bag = UnitOfMeasure::query()->where('code', 'BAG')->firstOrFail();

        $this->postJson('/api/v1/master-data/products', [
            'sku' => 'DEFAULT-GUARD',
            'name' => 'Default Guard Rice',
            'base_unit_id' => $kg->public_id,
            'packagings' => [
                [
                    'unit_id' => $bag->public_id,
                    'code' => 'BAG25',
                    'name' => '25kg Bag',
                    'conversion_to_base' => 25,
                    'is_default_sale' => true,
                ],
                [
                    'unit_id' => $bag->public_id,
                    'code' => 'BAG50-2',
                    'name' => '50kg Bag',
                    'conversion_to_base' => 50,
                    'is_default_sale' => true,
                ],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('packagings');
    }
}
