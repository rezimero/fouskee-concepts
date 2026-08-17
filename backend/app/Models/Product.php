<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'organization_id',
        'sku',
        'name',
        'description',
        'brand_id',
        'product_category_id',
        'base_unit_id',
        'status',
    ];

    protected function casts(): array
    {
        return ['status' => ProductStatus::class];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_unit_id');
    }

    public function packagings(): HasMany
    {
        return $this->hasMany(ProductPackaging::class);
    }
}
