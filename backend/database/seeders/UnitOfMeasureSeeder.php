<?php

namespace Database\Seeders;

use App\Enums\UnitKind;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->units() as $unit) {
            UnitOfMeasure::query()->updateOrCreate(
                [
                    'organization_id' => null,
                    'code' => $unit['code'],
                ],
                $unit + [
                    'organization_id' => null,
                    'is_system' => true,
                    'status' => 'active',
                ],
            );
        }
    }

    private function units(): array
    {
        return [
            [
                'code' => 'KG',
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'kind' => UnitKind::Mass,
                'decimal_places' => 3,
            ],
            [
                'code' => 'G',
                'name' => 'Gram',
                'symbol' => 'g',
                'kind' => UnitKind::Mass,
                'decimal_places' => 0,
            ],
            [
                'code' => 'BAG',
                'name' => 'Bag',
                'symbol' => 'bag',
                'kind' => UnitKind::Package,
                'decimal_places' => 0,
            ],
            [
                'code' => 'CTN',
                'name' => 'Carton',
                'symbol' => 'ctn',
                'kind' => UnitKind::Package,
                'decimal_places' => 0,
            ],
            [
                'code' => 'EA',
                'name' => 'Each',
                'symbol' => 'ea',
                'kind' => UnitKind::Count,
                'decimal_places' => 0,
            ],
        ];
    }
}
