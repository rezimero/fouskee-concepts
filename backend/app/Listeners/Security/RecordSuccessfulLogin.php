<?php

namespace App\Listeners\Security;

use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Auth\Events\Login;

class RecordSuccessfulLogin
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->audit->record(
            event: 'identity.login_succeeded',
            actor: $event->user,
            subject: $event->user,
        );
    }
}
