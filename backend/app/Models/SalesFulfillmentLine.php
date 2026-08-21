<?php

namespace App\Models;

use App\Models\Concerns\ImmutableAfterCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesFulfillmentLine extends Model
{
    use ImmutableAfterCreate;

    protected $fillable = [
        'inventory_reservation_id', 'product_id', 'inventory_lot_id',
        'quantity_base_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity_base_snapshot' => 'decimal:6',
        ];
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(SalesFulfillment::class, 'sales_fulfillment_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(InventoryReservation::class);
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
