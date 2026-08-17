<?php

namespace Tests\Feature\Commercial;

use App\Enums\UserStatus;
use App\Models\AuditEvent;
use App\Models\Customer;
use App\Models\Organization;
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

class CommercialMasterDataApiTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

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
            'code' => 'FC-TEST',
            'legal_name' => 'Fouskee Test',
            'trading_name' => 'Fouskee Test',
            'country_code' => 'NG',
            'currency_code' => 'NGN',
            'timezone' => 'Africa/Lagos',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'status' => UserStatus::Active,
            'default_organization_id' => $this->organization->id,
            'mfa_required' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        app(RoleAssignmentService::class)->assign(
            $this->user,
            Role::query()->where('code', 'superadmin')->firstOrFail(),
        );

        Sanctum::actingAs($this->user);
    }

    public function test_superadmin_can_create_customer_with_audit_event(): void
    {
        $response = $this->postJson('/api/v1/master-data/customers', [
            'segment' => 'wholesale',
            'display_name' => 'Apex Foods',
            'phone' => '08000000000',
            'credit_limit' => 2500000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.display_name', 'Apex Foods')
            ->assertJsonPath('data.segment', 'wholesale');

        $this->assertDatabaseHas('customers', [
            'organization_id' => $this->organization->id,
            'display_name' => 'Apex Foods',
        ]);

        $this->assertTrue(
            AuditEvent::query()->where('event', 'customer.created')->exists(),
        );
    }

    public function test_product_supports_multi_unit_packaging(): void
    {
        $kg = UnitOfMeasure::query()->where('code', 'KG')->firstOrFail();
        $bag = UnitOfMeasure::query()->where('code', 'BAG')->firstOrFail();

        $response = $this->postJson('/api/v1/master-data/products', [
            'sku' => 'RICE-001',
            'name' => 'Premium Rice',
            'base_unit_id' => $kg->public_id,
            'packagings' => [
                [
                    'unit_id' => $bag->public_id,
                    'code' => 'BAG50',
                    'name' => '50kg Bag',
                    'conversion_to_base' => 50,
                    'is_default_sale' => true,
                    'is_default_purchase' => true,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.packagings.0.code', 'BAG50')
            ->assertJsonPath('data.packagings.0.conversion_to_base', '50.000000');
    }

    public function test_customer_listing_is_scoped_to_default_organization(): void
    {
        $other = Organization::query()->create([
            'code' => 'OTHER',
            'legal_name' => 'Other Company',
            'trading_name' => 'Other Company',
            'country_code' => 'NG',
            'currency_code' => 'NGN',
            'timezone' => 'Africa/Lagos',
            'status' => 'active',
        ]);

        Customer::query()->create([
            'organization_id' => $other->id,
            'customer_number' => 'CUS-OTHER',
            'segment' => 'retail',
            'display_name' => 'Hidden Customer',
            'credit_limit' => 0,
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/master-data/customers')
            ->assertOk()
            ->assertJsonMissing(['display_name' => 'Hidden Customer']);
    }
}
