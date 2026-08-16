<?php

namespace App\Listeners\Security;

use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Auth\Events\Failed;

class RecordFailedLogin
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function handle(Failed $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;
        $email = mb_strtolower(trim((string) ($event->credentials['email'] ?? '')));

        $this->audit->record(
            event: 'identity.login_failed',
            actor: $user,
            subject: $user,
            context: [
                'email_hash' => $email === '' ? null : hash('sha256', $email),
            ],
        );
    }
}
