<?php

namespace App\Models;

use App\Enums\GoodsReceiptStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id', 'goods_receipt_number', 'purchase_order_id',
        'inventory_location_id', 'status', 'received_at', 'notes',
        'idempotency_key', 'idempotency_fingerprint', 'policy_version_id',
        'recorded_by_user_id', 'posted_by_user_id', 'rejected_by_user_id',
        'reversed_by_user_id', 'inventory_movement_id',
        'reversal_movement_id', 'posted_at', 'rejected_at', 'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => GoodsReceiptStatus::class,
            'received_at' => 'datetime',
            'posted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(
            InventoryLocation::class,
            'inventory_location_id',
        );
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }
}
