<?php

namespace App\Models;

use App\Enums\CommercialPartyStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'organization_id',
        'supplier_number',
        'display_name',
        'legal_name',
        'email',
        'phone',
        'tax_identifier',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommercialPartyStatus::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
