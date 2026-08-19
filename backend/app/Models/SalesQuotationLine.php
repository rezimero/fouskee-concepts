<?php

namespace App\Models;

use App\Enums\SalesQuotationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SalesQuotationLine extends Model
{
    protected $fillable = [
        'line_number', 'product_id', 'product_packaging_id',
        'product_sku_snapshot', 'product_name_snapshot',
        'packaging_name_snapshot', 'conversion_to_base_snapshot',
        'quoted_quantity', 'quoted_quantity_base', 'list_unit_price',
        'floor_unit_price', 'effective_unit_price', 'line_total',
        'price_exception',
    ];

    protected function casts(): array
    {
        return [
            'conversion_to_base_snapshot' => 'decimal:6',
            'quoted_quantity' => 'decimal:6',
            'quoted_quantity_base' => 'decimal:6',
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

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'sales_quotation_id');
    }

    private function assertDraft(): void
    {
        $status = $this->quotation()->value('status');

        if ($status !== SalesQuotationStatus::Draft->value) {
            throw new LogicException('Sent quotation evidence is immutable.');
        }
    }
}
