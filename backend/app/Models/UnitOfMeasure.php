<?php

namespace App\Models;

use App\Enums\UnitKind;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $table = 'units_of_measure';

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'symbol',
        'kind',
        'decimal_places',
        'is_system',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'kind' => UnitKind::class,
            'decimal_places' => 'integer',
            'is_system' => 'boolean',
        ];
    }
}
