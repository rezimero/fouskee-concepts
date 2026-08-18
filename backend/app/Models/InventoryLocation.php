<?php

namespace App\Models;

use App\Enums\InventoryLocationStatus;
use App\Enums\InventoryLocationType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLocation extends Model
{
    use HasPublicId;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => InventoryLocationType::class,
            'status' => InventoryLocationStatus::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
