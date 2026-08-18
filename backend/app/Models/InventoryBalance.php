<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBalance extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'inventory_location_id', 'product_id',
        'inventory_lot_id', 'lot_scope_key', 'quantity_on_hand_base',
        'inventory_value', 'weighted_average_unit_cost', 'version', 'projected_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand_base' => 'decimal:6',
            'inventory_value' => 'decimal:6',
            'weighted_average_unit_cost' => 'decimal:6',
            'version' => 'integer',
            'projected_at' => 'datetime',
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
