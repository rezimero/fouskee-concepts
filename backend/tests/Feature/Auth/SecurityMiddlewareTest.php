<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\RoleAssignmentService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityMiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        Route::middleware(['auth:sanctum', 'user.active', 'mfa.enrolled'])
            ->get('/_test/mfa', fn () => response()->json(['ok' => true]));

        Route::middleware(['auth:sanctum', 'user.active', 'permission:audit.view'])
            ->get('/_test/permission', fn () => response()->json(['ok' => true]));

        Route::middleware(['auth:sanctum', 'privileged.reason'])
            ->post('/_test/reason', fn () => response()->json(['ok' => true]));
    }

    public function test_required_mfa_blocks_privileged_access_until_enrolled(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'mfa_required' => true,
            'two_factor_confirmed_at' => null,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/_test/mfa')
            ->assertStatus(428)
            ->assertJsonPath('code', 'mfa_enrollment_required');
    }

    public function test_superadmin_passes_permission_middleware(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $role = Role::query()->where('code', 'superadmin')->firstOrFail();

        app(RoleAssignmentService::class)->assign($user, $role);
        Sanctum::actingAs($user);

        $this->getJson('/_test/permission')->assertOk();
    }

    public function test_privileged_reason_is_mandatory(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        Sanctum::actingAs($user);

        $this->postJson('/_test/reason')
            ->assertUnprocessable()
            ->assertJsonPath('code', 'privileged_reason_required');
    }
}
