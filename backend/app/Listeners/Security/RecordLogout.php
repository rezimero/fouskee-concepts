<?php

namespace App\Listeners\Security;

use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Auth\Events\Logout;

class RecordLogout
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->audit->record(
            event: 'identity.logout',
            actor: $event->user,
            subject: $event->user,
        );
    }
}
