<?php

namespace App\Models;

use App\Enums\SalesFulfillmentStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesFulfillment extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id', 'sales_order_id', 'inventory_location_id',
        'fulfillment_number', 'status', 'pick_idempotency_key',
        'pick_idempotency_fingerprint', 'picked_by_user_id', 'picked_at',
        'dispatch_idempotency_key', 'inventory_movement_id',
        'dispatched_by_user_id', 'dispatched_at', 'reversal_idempotency_key',
        'reversal_movement_id', 'reversed_by_user_id', 'reversed_at',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesFulfillmentStatus::class,
            'picked_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function reversalMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'reversal_movement_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesFulfillmentLine::class);
    }
}
