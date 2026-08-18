<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementPolicyVersion extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id',
        'effective_from',
        'purchase_order_approval_value_threshold',
        'over_receipt_tolerance_percent',
        'invoice_quantity_tolerance_percent',
        'invoice_price_tolerance_percent',
        'invoice_amount_tolerance_percent',
        'created_by_user_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'datetime',
            'purchase_order_approval_value_threshold' => 'decimal:6',
            'over_receipt_tolerance_percent' => 'decimal:4',
            'invoice_quantity_tolerance_percent' => 'decimal:4',
            'invoice_price_tolerance_percent' => 'decimal:4',
            'invoice_amount_tolerance_percent' => 'decimal:4',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
