<?php

namespace Tests\Feature\Commercial;

use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\PriceList;
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

class PricingControlTest extends TestCase
{
    use DatabaseTransactions;

    private string $priceListId;

    private string $packagingId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            UnitOfMeasureSeeder::class,
        ]);

        $organization = Organization::query()->create([
            'code' => 'PRICE-TEST',
            'legal_name' => 'Pricing Test',
            'trading_name' => 'Pricing Test',
            'country_code' => 'NG',
            'currency_code' => 'NGN',
            'timezone' => 'Africa/Lagos',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'default_organization_id' => $organization->id,
            'mfa_required' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        app(RoleAssignmentService::class)->assign(
            $user,
            Role::query()->where('code', 'superadmin')->firstOrFail(),
        );

        Sanctum::actingAs($user);

        $priceList = PriceList::query()->create([
            'organization_id' => $organization->id,
            'code' => 'WHOLESALE',
            'name' => 'Wholesale',
            'currency_code' => 'NGN',
            'status' => 'draft',
        ]);

        $kg = UnitOfMeasure::query()->where('code', 'KG')->firstOrFail();
        $bag = UnitOfMeasure::query()->where('code', 'BAG')->firstOrFail();

        $product = Product::query()->create([
            'organization_id' => $organization->id,
            'sku' => 'RICE-P',
            'name' => 'Pricing Rice',
            'base_unit_id' => $kg->id,
            'status' => 'active',
        ]);

        $packaging = ProductPackaging::query()->create([
            'product_id' => $product->id,
            'unit_of_measure_id' => $bag->id,
            'code' => 'BAG50',
            'name' => '50kg Bag',
            'conversion_to_base' => 50,
            'status' => 'active',
        ]);

        $this->priceListId = $priceList->public_id;
        $this->packagingId = $packaging->public_id;
    }

    public function test_price_change_requires_privileged_reason(): void
    {
        $this->putJson($this->endpoint(), [
            'selling_price' => 80000,
            'minimum_selling_price' => 75000,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'privileged_reason_required');
    }

    public function test_minimum_price_cannot_exceed_selling_price(): void
    {
        $this->withHeader('X-Privileged-Reason', 'Initial wholesale pricing')
            ->putJson($this->endpoint(), [
                'selling_price' => 80000,
                'minimum_selling_price' => 85000,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('minimum_selling_price');
    }

    public function test_valid_price_floor_is_persisted(): void
    {
        $this->withHeader('X-Privileged-Reason', 'Initial wholesale pricing')
            ->putJson($this->endpoint(), [
                'selling_price' => 80000,
                'minimum_selling_price' => 75000,
                'maximum_discount_percent' => 5,
            ])
            ->assertOk()
            ->assertJsonPath('data.minimum_selling_price', '75000.00');

        $this->assertDatabaseHas('price_list_items', [
            'selling_price' => 80000,
            'minimum_selling_price' => 75000,
        ]);
    }

    private function endpoint(): string
    {
        return "/api/v1/master-data/price-lists/{$this->priceListId}"
            ."/items/{$this->packagingId}";
    }
}
