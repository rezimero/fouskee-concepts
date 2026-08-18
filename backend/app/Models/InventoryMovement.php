<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use App\Models\Concerns\HasPublicId;
use App\Models\Concerns\ImmutableAfterCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryMovement extends Model
{
    use HasPublicId;
    use ImmutableAfterCreate;

    protected $fillable = [
        'organization_id',
        'movement_number',
        'movement_type',
        'occurred_at',
        'posted_at',
        'posted_by_user_id',
        'idempotency_key',
        'idempotency_fingerprint',
        'source_reference_type',
        'source_reference_public_id',
        'reversal_of_movement_id',
        'business_reason',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => InventoryMovementType::class,
            'occurred_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryMovementLine::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_movement_id');
    }
}
