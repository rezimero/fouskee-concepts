<?php

namespace App\Models;

use App\Models\Concerns\ImmutableAfterCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovementLine extends Model
{
    use ImmutableAfterCreate;

    public $timestamps = false;

    protected $fillable = [
        'inventory_movement_id',
        'inventory_location_id',
        'product_id',
        'product_packaging_id',
        'inventory_lot_id',
        'entered_quantity',
        'conversion_to_base_snapshot',
        'quantity_base',
        'unit_cost_snapshot',
        'inventory_value_delta',
    ];

    protected function casts(): array
    {
        return [
            'entered_quantity' => 'decimal:6',
            'conversion_to_base_snapshot' => 'decimal:6',
            'quantity_base' => 'decimal:6',
            'unit_cost_snapshot' => 'decimal:6',
            'inventory_value_delta' => 'decimal:6',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(ProductPackaging::class, 'product_packaging_id');
    }
}
