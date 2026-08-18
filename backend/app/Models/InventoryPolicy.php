<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryPolicy extends Model
{
    protected $fillable = [
        'organization_id',
        'count_variance_approval_value_threshold',
        'backdate_window_days',
        'require_separate_transfer_receiver',
        'allow_immediate_transfers',
    ];

    protected function casts(): array
    {
        return [
            'count_variance_approval_value_threshold' => 'decimal:6',
            'backdate_window_days' => 'integer',
            'require_separate_transfer_receiver' => 'boolean',
            'allow_immediate_transfers' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
