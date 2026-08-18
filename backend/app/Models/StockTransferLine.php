<?php

namespace App\Models;

use App\Models\Concerns\ImmutableAfterCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferLine extends Model
{
    use ImmutableAfterCreate;

    public $timestamps = false;

    protected $fillable = [
        'stock_transfer_id', 'product_id', 'product_packaging_id',
        'inventory_lot_id', 'entered_quantity',
        'conversion_to_base_snapshot', 'quantity_base',
    ];

    protected function casts(): array
    {
        return [
            'entered_quantity' => 'decimal:6',
            'conversion_to_base_snapshot' => 'decimal:6',
            'quantity_base' => 'decimal:6',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(ProductPackaging::class, 'product_packaging_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }
}
