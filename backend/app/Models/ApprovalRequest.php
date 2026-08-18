<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequest extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id',
        'subject_type',
        'subject_public_id',
        'action',
        'status',
        'requested_by_user_id',
        'decided_by_user_id',
        'requested_at',
        'decided_at',
        'decision_reason',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
            'context' => 'array',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
