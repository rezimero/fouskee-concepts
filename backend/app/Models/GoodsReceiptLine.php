<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Models\Concerns\ImmutableAfterCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptLine extends Model
{
    use HasPublicId;
    use ImmutableAfterCreate;

    public $timestamps = false;

    protected $fillable = [
        'goods_receipt_id', 'purchase_order_line_id', 'product_id',
        'product_packaging_id', 'received_quantity',
        'conversion_to_base_snapshot', 'received_quantity_base',
        'unit_cost_base_snapshot', 'lot_code', 'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'received_quantity' => 'decimal:6',
            'conversion_to_base_snapshot' => 'decimal:6',
            'received_quantity_base' => 'decimal:6',
            'unit_cost_base_snapshot' => 'decimal:6',
            'expiry_date' => 'date',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
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
