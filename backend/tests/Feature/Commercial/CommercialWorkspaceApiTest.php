<?php

namespace Tests\Feature\Commercial;

use App\Enums\UserStatus;
use App\Models\AuditEvent;
use App\Models\Organization;
use App\Models\PriceList;
use App\Models\PriceListItem;
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

class CommercialWorkspaceApiTest extends TestCase
{
    use DatabaseTransactions;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            UnitOfMeasureSeeder::class,
        ]);

        $this->organization = Organization::query()->create([
            'code' => 'WORKSPACE',
            'legal_name' => 'Workspace Test',
            'trading_name' => 'Workspace Test',
            'country_code' => 'NG',
            'currency_code' => 'NGN',
            'timezone' => 'Africa/Lagos',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'default_organization_id' => $this->organization->id,
            'mfa_required' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        app(RoleAssignmentService::class)->assign(
            $user,
            Role::query()->where('code', 'superadmin')->firstOrFail(),
        );

        Sanctum::actingAs($user);
    }

    public function test_catalog_references_include_system_units(): void
    {
        $this->getJson('/api/v1/master-data/catalog/references')
            ->assertOk()
            ->assertJsonFragment([
                'code' => 'KG',
                'name' => 'Kilogram',
            ]);
    }

    public function test_brand_and_category_creation_are_audited(): void
    {
        $this->postJson('/api/v1/master-data/brands', [
            'code' => 'ROYAL',
            'name' => 'Royal Stallion',
        ])->assertCreated();

        $this->postJson('/api/v1/master-data/categories', [
            'code' => 'RICE',
            'name' => 'Rice',
        ])->assertCreated();

        $this->assertTrue(
            AuditEvent::query()->where('event', 'catalog.brand.created')->exists(),
        );
        $this->assertTrue(
            AuditEvent::query()->where('event', 'catalog.category.created')->exists(),
        );
    }

    public function test_price_list_items_are_available_to_workspace(): void
    {
        $kg = UnitOfMeasure::query()->where('code', 'KG')->firstOrFail();
        $bag = UnitOfMeasure::query()->where('code', 'BAG')->firstOrFail();

        $product = Product::query()->create([
            'organization_id' => $this->organization->id,
            'sku' => 'UI-RICE',
            'name' => 'UI Rice',
            'base_unit_id' => $kg->id,
            'status' => 'active',
        ]);

        $packaging = ProductPackaging::query()->create([
            'product_id' => $product->id,
            'unit_of_measure_id' => $bag->id,
            'code' => '50KG',
            'name' => '50kg Bag',
            'conversion_to_base' => 50,
            'status' => 'active',
        ]);

        $priceList = PriceList::query()->create([
            'organization_id' => $this->organization->id,
            'code' => 'WHOLESALE',
            'name' => 'Wholesale',
            'currency_code' => 'NGN',
            'status' => 'draft',
        ]);

        PriceListItem::query()->create([
            'price_list_id' => $priceList->id,
            'product_packaging_id' => $packaging->id,
            'selling_price' => 80000,
            'minimum_selling_price' => 76000,
        ]);

        $this->getJson(
            "/api/v1/master-data/price-lists/{$priceList->public_id}/items",
        )
            ->assertOk()
            ->assertJsonFragment([
                'product_name' => 'UI Rice',
                'packaging_name' => '50kg Bag',
                'selling_price' => '80000.00',
            ]);
    }
}
