<?php

namespace Tests\Support;

use App\Enums\UserStatus;
use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductPackaging;
use App\Models\Role;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Authorization\RoleAssignmentService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitOfMeasureSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class InventoryTestCase extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Organization $organization;

    protected Product $product;

    protected ProductPackaging $packaging;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            UnitOfMeasureSeeder::class,
        ]);

        $this->organization = Organization::query()->create([
            'code' => 'INV-TEST',
            'legal_name' => 'Inventory Test',
            'trading_name' => 'Inventory Test',
            'country_code' => 'NG',
            'currency_code' => 'NGN',
            'timezone' => 'Africa/Lagos',
            'status' => 'active',
        ]);

        $this->user = $this->superadmin('inventory-maker@example.test');
        Sanctum::actingAs($this->user);

        $kg = UnitOfMeasure::query()->where('code', 'KG')->firstOrFail();
        $bag = UnitOfMeasure::query()->where('code', 'BAG')->firstOrFail();

        $this->product = Product::query()->create([
            'organization_id' => $this->organization->id,
            'sku' => 'INV-RICE',
            'name' => 'Inventory Rice',
            'base_unit_id' => $kg->id,
            'status' => 'active',
        ]);

        $this->packaging = ProductPackaging::query()->create([
            'product_id' => $this->product->id,
            'unit_of_measure_id' => $bag->id,
            'code' => 'BAG50',
            'name' => '50kg Bag',
            'conversion_to_base' => 50,
            'is_default_sale' => true,
            'is_default_purchase' => true,
            'status' => 'active',
        ]);
    }

    protected function superadmin(string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'status' => UserStatus::Active,
            'default_organization_id' => $this->organization->id,
            'mfa_required' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        app(RoleAssignmentService::class)->assign(
            $user,
            Role::query()->where('code', 'superadmin')->firstOrFail(),
        );

        return $user;
    }

    protected function location(string $code): InventoryLocation
    {
        return InventoryLocation::query()->create([
            'organization_id' => $this->organization->id,
            'code' => $code,
            'name' => "{$code} Location",
            'type' => 'warehouse',
            'status' => 'active',
        ]);
    }

    protected function receipt(
        InventoryLocation $location,
        int|float $quantity = 2,
        string $key = 'receipt-key-0001',
        int|float $unitCost = 1000,
        array $extra = [],
    ) {
        return $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/inventory/receipts', [
                'location_id' => $location->public_id,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'packaging_id' => $this->packaging->public_id,
                    'quantity' => $quantity,
                    'unit_cost_base' => $unitCost,
                    ...$extra,
                ]],
            ]);
    }
}
