<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_active_user_can_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.test',
            'password' => 'Correct-Horse-Battery-123',
            'status' => UserStatus::Active,
        ]);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'Correct-Horse-Battery-123',
        ])->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_suspended_user_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.test',
            'password' => 'Correct-Horse-Battery-123',
            'status' => UserStatus::Suspended,
        ]);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'Correct-Horse-Battery-123',
        ])->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_public_registration_route_is_disabled(): void
    {
        $this->postJson('/register', [
            'name' => 'Unauthorized Staff',
            'email' => 'unauthorized@example.test',
            'password' => 'Correct-Horse-Battery-123',
            'password_confirmation' => 'Correct-Horse-Battery-123',
        ])->assertNotFound();
    }
}
