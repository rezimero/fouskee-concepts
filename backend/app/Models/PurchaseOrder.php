<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id', 'purchase_order_number', 'supplier_id', 'status',
        'currency_code', 'expected_delivery_date', 'notes', 'total_amount',
        'idempotency_key', 'idempotency_fingerprint', 'policy_version_id',
        'created_by_user_id', 'submitted_by_user_id', 'approved_by_user_id',
        'cancelled_by_user_id', 'submitted_at', 'approved_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'expected_delivery_date' => 'date',
            'total_amount' => 'decimal:6',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(
            ProcurementPolicyVersion::class,
            'policy_version_id',
        );
    }
}
