<?php

namespace App\Models;

use App\Enums\ProcurementMatchResult;
use App\Models\Concerns\HasPublicId;
use App\Models\Concerns\ImmutableAfterCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementMatchRun extends Model
{
    use HasPublicId;
    use ImmutableAfterCreate;

    protected $fillable = [
        'organization_id', 'supplier_invoice_id', 'run_number',
        'result', 'expected_amount', 'invoiced_amount',
        'amount_variance', 'policy_version_id', 'idempotency_key',
        'idempotency_fingerprint', 'matched_by_user_id', 'matched_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => ProcurementMatchResult::class,
            'expected_amount' => 'decimal:6',
            'invoiced_amount' => 'decimal:6',
            'amount_variance' => 'decimal:6',
            'matched_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ProcurementMatchLine::class, 'match_run_id');
    }
}
