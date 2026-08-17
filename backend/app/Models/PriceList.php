<?php

namespace App\Models;

use App\Enums\PriceListStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'currency_code',
        'status',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'status' => PriceListStatus::class,
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }
}
