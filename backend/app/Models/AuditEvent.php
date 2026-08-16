<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditEvent extends Model
{
    use HasPublicId;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'actor_user_id',
        'impersonated_user_id',
        'event',
        'subject_type',
        'subject_id',
        'reason',
        'request_id',
        'ip_address',
        'user_agent',
        'route_name',
        'method',
        'before',
        'after',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit events are immutable.'));
        static::deleting(fn () => throw new LogicException('Audit events are immutable.'));
    }
}
