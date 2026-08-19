<?php

namespace App\Models;

use App\Enums\SalesQuotationStatus;
use App\Enums\SalesSettlementType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesQuotation extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id', 'quotation_number', 'customer_id', 'price_list_id',
        'status', 'settlement_type', 'currency_code', 'subtotal_amount',
        'total_amount', 'valid_until', 'notes', 'idempotency_key',
        'idempotency_fingerprint', 'created_by_user_id', 'sent_by_user_id',
        'accepted_by_user_id', 'converted_by_user_id', 'sent_at',
        'accepted_at', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesQuotationStatus::class,
            'settlement_type' => SalesSettlementType::class,
            'subtotal_amount' => 'decimal:6',
            'total_amount' => 'decimal:6',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'converted_at' => 'datetime',
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
        return $this->hasMany(SalesQuotationLine::class);
    }
}
