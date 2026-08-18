<?php

namespace App\Models;

use App\Enums\SupplierInvoiceStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id', 'supplier_id', 'invoice_number',
        'normalized_invoice_number', 'invoice_date', 'currency_code',
        'status', 'subtotal_amount', 'tax_amount', 'total_amount',
        'idempotency_key', 'idempotency_fingerprint',
        'recorded_by_user_id', 'latest_match_run_id',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'status' => SupplierInvoiceStatus::class,
            'subtotal_amount' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'total_amount' => 'decimal:6',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierInvoiceLine::class);
    }

    public function latestMatchRun(): BelongsTo
    {
        return $this->belongsTo(
            ProcurementMatchRun::class,
            'latest_match_run_id',
        );
    }
}
