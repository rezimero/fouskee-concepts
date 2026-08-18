<?php

namespace App\Models;

use App\Enums\StockCountStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCountSession extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id', 'count_number', 'inventory_location_id', 'status',
        'reason_code', 'notes', 'total_abs_variance_value',
        'submission_idempotency_key', 'approval_idempotency_key', 'approval_request_id',
        'created_by_user_id', 'submitted_by_user_id', 'approved_by_user_id',
        'posted_by_user_id', 'posted_movement_id',
        'submitted_at', 'approved_at', 'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StockCountStatus::class,
            'total_abs_variance_value' => 'decimal:6',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function postedMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'posted_movement_id');
    }
}
