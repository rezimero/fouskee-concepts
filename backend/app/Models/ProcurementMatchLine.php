<?php

namespace App\Models;

use App\Enums\ProcurementMatchResult;
use App\Models\Concerns\ImmutableAfterCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementMatchLine extends Model
{
    use ImmutableAfterCreate;

    public $timestamps = false;

    protected $fillable = [
        'match_run_id', 'supplier_invoice_line_id', 'purchase_order_line_id',
        'result', 'ordered_quantity_base', 'received_quantity_base',
        'invoiced_quantity_base', 'cumulative_invoiced_quantity_base',
        'ordered_unit_price_base', 'invoiced_unit_price_base',
        'expected_amount', 'invoiced_amount', 'quantity_variance_base',
        'price_variance_base', 'amount_variance', 'exception_codes',
    ];

    protected function casts(): array
    {
        return [
            'result' => ProcurementMatchResult::class,
            'ordered_quantity_base' => 'decimal:6',
            'received_quantity_base' => 'decimal:6',
            'invoiced_quantity_base' => 'decimal:6',
            'cumulative_invoiced_quantity_base' => 'decimal:6',
            'ordered_unit_price_base' => 'decimal:6',
            'invoiced_unit_price_base' => 'decimal:6',
            'expected_amount' => 'decimal:6',
            'invoiced_amount' => 'decimal:6',
            'quantity_variance_base' => 'decimal:6',
            'price_variance_base' => 'decimal:6',
            'amount_variance' => 'decimal:6',
            'exception_codes' => 'array',
        ];
    }

    public function invoiceLine(): BelongsTo
    {
        return $this->belongsTo(
            SupplierInvoiceLine::class,
            'supplier_invoice_line_id',
        );
    }
}
