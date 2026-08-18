<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class PurchaseOrderLine extends Model
{
    use HasPublicId;

    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id', 'line_number', 'product_id',
        'product_packaging_id', 'product_sku_snapshot',
        'product_name_snapshot', 'packaging_name_snapshot',
        'conversion_to_base_snapshot', 'ordered_quantity',
        'ordered_quantity_base', 'unit_price', 'unit_price_base',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'conversion_to_base_snapshot' => 'decimal:6',
            'ordered_quantity' => 'decimal:6',
            'ordered_quantity_base' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'unit_price_base' => 'decimal:6',
            'line_total' => 'decimal:6',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (self $line) => $line->assertDraftParent());
        static::deleting(fn (self $line) => $line->assertDraftParent());
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(ProductPackaging::class, 'product_packaging_id');
    }

    public function receiptLines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    private function assertDraftParent(): void
    {
        $status = PurchaseOrder::query()
            ->whereKey($this->purchase_order_id)
            ->value('status');

        if ($status !== 'draft') {
            throw new LogicException(
                'Submitted purchase-order lines cannot be changed in place.',
            );
        }
    }
}
