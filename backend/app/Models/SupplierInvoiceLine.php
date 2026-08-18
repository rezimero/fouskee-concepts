<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Models\Concerns\ImmutableAfterCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoiceLine extends Model
{
    use HasPublicId;
    use ImmutableAfterCreate;

    public $timestamps = false;

    protected $fillable = [
        'supplier_invoice_id', 'line_number', 'purchase_order_line_id',
        'product_id', 'product_packaging_id', 'product_sku_snapshot',
        'product_name_snapshot', 'packaging_name_snapshot',
        'conversion_to_base_snapshot', 'invoiced_quantity',
        'invoiced_quantity_base', 'unit_price', 'unit_price_base',
        'line_amount',
    ];

    protected function casts(): array
    {
        return [
            'conversion_to_base_snapshot' => 'decimal:6',
            'invoiced_quantity' => 'decimal:6',
            'invoiced_quantity_base' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'unit_price_base' => 'decimal:6',
            'line_amount' => 'decimal:6',
        ];
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
