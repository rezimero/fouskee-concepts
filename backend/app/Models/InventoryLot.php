<?php

namespace App\Models;

use App\Enums\InventoryLotStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLot extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id',
        'product_id',
        'code',
        'received_at',
        'expiry_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'expiry_date' => 'date',
            'status' => InventoryLotStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
