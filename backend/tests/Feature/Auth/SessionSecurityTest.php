<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_list_and_revoke_another_session(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'mfa_required' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        $sessionId = 'test-session-id';
        $this->insertSession($user, $sessionId);

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/security/sessions')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $reference = $response->json('data.0.reference');

        $this->deleteJson(
            "/api/v1/security/sessions/{$reference}",
            ['password' => 'password'],
            ['X-Privileged-Reason' => 'Lost access to an old staff laptop.'],
        )->assertNoContent();

        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);

        $this->assertTrue(
            AuditEvent::query()
                ->where('event', 'security.session_revoked')
                ->where('actor_user_id', $user->id)
                ->exists(),
        );
    }

    private function insertSession(User $user, string $sessionId): void
    {
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Fouskee Test Browser',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);
    }
}
