<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\RoleAssignmentService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeEndpointTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_read_identity_context(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_active_user_can_read_identity_context(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'mfa_required' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->public_id)
            ->assertJsonPath('data.mfa.required', true)
            ->assertJsonPath('data.mfa.enrolled', false);
    }

    public function test_superadmin_receives_wildcard_permission_context(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $role = Role::query()->where('code', 'superadmin')->firstOrFail();

        app(RoleAssignmentService::class)->assign($user, $role);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.permissions.0', '*');
    }

    public function test_suspended_user_is_rejected(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'account_inactive');
    }
}
