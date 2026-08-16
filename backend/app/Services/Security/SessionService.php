<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SessionService
{
    public function forUser(User $user, ?string $currentSessionId): array
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => [
                'reference' => $this->reference($session->id),
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_active_at' => date(DATE_ATOM, $session->last_activity),
                'current' => $currentSessionId !== null
                    && hash_equals($session->id, $currentSessionId),
            ])
            ->all();
    }

    public function revoke(
        User $user,
        string $reference,
        ?string $currentSessionId,
    ): void {
        $sessionId = $this->resolveReference($user, $reference);

        if ($sessionId === null) {
            throw ValidationException::withMessages([
                'session' => 'The selected session was not found.',
            ]);
        }

        if ($currentSessionId !== null && hash_equals($sessionId, $currentSessionId)) {
            throw ValidationException::withMessages([
                'session' => 'Use logout to end the current session.',
            ]);
        }

        DB::table('sessions')->where('id', $sessionId)->delete();
    }

    private function resolveReference(User $user, string $reference): ?string
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->pluck('id')
            ->first(fn (string $id) => hash_equals(
                $this->reference($id),
                $reference,
            ));
    }

    private function reference(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, (string) config('app.key'));
    }
}
