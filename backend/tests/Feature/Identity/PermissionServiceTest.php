<?php

namespace Tests\Feature\Identity;

use App\Enums\PermissionCode;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\PermissionService;
use App\Services\Authorization\RoleAssignmentService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_global_superadmin_bypasses_named_permissions(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $role = Role::query()->where('code', 'superadmin')->firstOrFail();

        app(RoleAssignmentService::class)->assign($user, $role);

        $this->assertTrue(
            app(PermissionService::class)->allows($user, PermissionCode::BreakGlass),
        );
    }

    public function test_organization_role_is_scoped_to_its_organization(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $first = Organization::query()->create(['code' => 'ORG-A', 'legal_name' => 'Org A']);
        $second = Organization::query()->create(['code' => 'ORG-B', 'legal_name' => 'Org B']);
        $role = Role::query()->where('code', 'administrator')->firstOrFail();

        app(RoleAssignmentService::class)->assign($user, $role, $first);

        $service = app(PermissionService::class);

        $this->assertTrue($service->allows($user, PermissionCode::UserManage, $first));
        $this->assertFalse($service->allows($user, PermissionCode::UserManage, $second));
    }

    public function test_suspended_user_is_denied_even_with_superadmin_role(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);
        $role = Role::query()->where('code', 'superadmin')->firstOrFail();

        app(RoleAssignmentService::class)->assign($user, $role);

        $this->assertFalse(
            app(PermissionService::class)->allows($user, PermissionCode::AuditView),
        );
    }
}
