<?php

namespace App\Models;

use App\Enums\InventoryReservationStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReservation extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id', 'inventory_location_id', 'product_id',
        'inventory_lot_id', 'source_reference_type',
        'source_reference_public_id', 'quantity_base', 'status',
        'expires_at', 'created_by_user_id', 'released_by_user_id', 'released_at',
        'fulfilled_by_user_id', 'fulfilled_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_base' => 'decimal:6',
            'status' => InventoryReservationStatus::class,
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }
}
