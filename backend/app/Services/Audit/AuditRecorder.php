<?php

namespace App\Services\Audit;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditRecorder
{
    public function record(
        string $event,
        ?User $actor = null,
        ?Model $subject = null,
        ?int $organizationId = null,
        array $before = [],
        array $after = [],
        array $context = [],
        ?string $reason = null,
        ?User $impersonatedUser = null,
    ): AuditEvent {
        $request = app()->bound('request') ? request() : null;

        return AuditEvent::query()->create([
            'organization_id' => $organizationId ?? $actor?->default_organization_id,
            'actor_user_id' => $actor?->id,
            'impersonated_user_id' => $impersonatedUser?->id,
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $this->subjectId($subject),
            'reason' => $reason,
            'request_id' => $this->requestId($request),
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 512, ''),
            'route_name' => $request?->route()?->getName(),
            'method' => $request?->method(),
            'before' => $before ?: null,
            'after' => $after ?: null,
            'context' => $context ?: null,
            'occurred_at' => now(),
        ]);
    }

    private function subjectId(?Model $subject): ?string
    {
        if ($subject === null) {
            return null;
        }

        return (string) ($subject->getAttribute('public_id') ?: $subject->getKey());
    }

    private function requestId(?Request $request): string
    {
        return (string) ($request?->headers->get('X-Request-ID') ?: Str::ulid());
    }
}
