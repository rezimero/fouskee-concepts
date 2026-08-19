<?php

namespace App\Models;

use App\Enums\SalesOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SalesOrderLine extends Model
{
    protected $fillable = [
        'line_number', 'product_id', 'product_packaging_id',
        'product_sku_snapshot', 'product_name_snapshot',
        'packaging_name_snapshot', 'conversion_to_base_snapshot',
        'ordered_quantity', 'ordered_quantity_base', 'list_unit_price',
        'floor_unit_price', 'effective_unit_price', 'line_total',
        'price_exception',
    ];

    protected function casts(): array
    {
        return [
            'conversion_to_base_snapshot' => 'decimal:6',
            'ordered_quantity' => 'decimal:6',
            'ordered_quantity_base' => 'decimal:6',
            'list_unit_price' => 'decimal:6',
            'floor_unit_price' => 'decimal:6',
            'effective_unit_price' => 'decimal:6',
            'line_total' => 'decimal:6',
            'price_exception' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (self $line) => $line->assertDraft());
        static::deleting(fn (self $line) => $line->assertDraft());
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(ProductPackaging::class, 'product_packaging_id');
    }

    private function assertDraft(): void
    {
        $status = $this->order()->value('status');

        if ($status !== SalesOrderStatus::Draft->value) {
            throw new LogicException('Submitted sales-order evidence is immutable.');
        }
    }
}
