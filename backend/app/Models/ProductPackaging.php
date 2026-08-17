<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPackaging extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'product_id',
        'unit_of_measure_id',
        'code',
        'name',
        'barcode',
        'conversion_to_base',
        'is_default_sale',
        'is_default_purchase',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'conversion_to_base' => 'decimal:6',
            'is_default_sale' => 'boolean',
            'is_default_purchase' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_of_measure_id');
    }
}
