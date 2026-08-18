<?php

namespace App\Models;

use App\Enums\StockTransferStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id', 'transfer_number', 'source_location_id',
        'destination_location_id', 'transit_location_id', 'status', 'notes',
        'created_by_user_id', 'dispatched_by_user_id', 'received_by_user_id',
        'cancelled_by_user_id', 'dispatch_movement_id', 'receive_movement_id',
        'cancel_movement_id', 'dispatch_idempotency_key',
        'receive_idempotency_key', 'cancel_idempotency_key',
        'dispatched_at', 'received_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StockTransferStatus::class,
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class);
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'destination_location_id');
    }

    public function transitLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'transit_location_id');
    }
}
