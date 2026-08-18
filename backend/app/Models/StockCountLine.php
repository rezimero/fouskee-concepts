<?php

namespace App\Models;

use App\Models\Concerns\ImmutableAfterCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountLine extends Model
{
    use ImmutableAfterCreate;

    public $timestamps = false;

    protected $fillable = [
        'stock_count_session_id', 'product_id', 'inventory_lot_id',
        'system_quantity_base', 'counted_quantity_base', 'variance_quantity_base',
        'unit_cost_snapshot', 'variance_value', 'balance_version_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity_base' => 'decimal:6',
            'counted_quantity_base' => 'decimal:6',
            'variance_quantity_base' => 'decimal:6',
            'unit_cost_snapshot' => 'decimal:6',
            'variance_value' => 'decimal:6',
            'balance_version_snapshot' => 'integer',
        ];
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
