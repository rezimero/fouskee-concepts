<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_list_id',
        'product_packaging_id',
        'selling_price',
        'minimum_selling_price',
        'maximum_discount_percent',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'minimum_selling_price' => 'decimal:2',
            'maximum_discount_percent' => 'decimal:2',
        ];
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(ProductPackaging::class, 'product_packaging_id');
    }
}
