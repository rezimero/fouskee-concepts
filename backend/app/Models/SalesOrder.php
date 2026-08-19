<?php

namespace App\Models;

use App\Enums\SalesOrderStatus;
use App\Enums\SalesSettlementType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id', 'sales_order_number', 'customer_id', 'price_list_id',
        'status', 'settlement_type', 'currency_code', 'subtotal_amount',
        'total_amount', 'credit_limit_snapshot',
        'credit_exposure_before_snapshot', 'exception_codes', 'notes',
        'idempotency_key', 'idempotency_fingerprint', 'created_by_user_id',
        'submitted_by_user_id', 'approved_by_user_id', 'cancelled_by_user_id',
        'submitted_at', 'approved_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatus::class,
            'settlement_type' => SalesSettlementType::class,
            'subtotal_amount' => 'decimal:6',
            'total_amount' => 'decimal:6',
            'credit_limit_snapshot' => 'decimal:6',
            'credit_exposure_before_snapshot' => 'decimal:6',
            'exception_codes' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }
}
