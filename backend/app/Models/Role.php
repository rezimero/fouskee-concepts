<?php

namespace App\Models;

use App\Enums\RoleScope;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasPublicId;

    protected $fillable = [
        'code',
        'name',
        'description',
        'scope',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'scope' => RoleScope::class,
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot('granted_by')
            ->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class);
    }
}
