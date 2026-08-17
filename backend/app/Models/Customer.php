<?php

namespace App\Models;

use App\Enums\CommercialPartyStatus;
use App\Enums\CustomerSegment;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'organization_id',
        'customer_number',
        'segment',
        'display_name',
        'legal_name',
        'email',
        'phone',
        'tax_identifier',
        'credit_limit',
        'default_price_list_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'segment' => CustomerSegment::class,
            'status' => CommercialPartyStatus::class,
            'credit_limit' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function defaultPriceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'default_price_list_id');
    }
}
