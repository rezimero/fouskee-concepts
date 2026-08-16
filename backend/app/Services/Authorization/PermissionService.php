<?php

namespace App\Services\Authorization;

use App\Enums\PermissionCode;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Builder;

class PermissionService
{
    public function allows(
        User $user,
        PermissionCode|string $permission,
        ?Organization $organization = null,
    ): bool {
        if ($user->status !== UserStatus::Active) {
            return false;
        }

        $assignments = $this->activeAssignments($user, $organization);

        if ((clone $assignments)
            ->whereHas('role', fn (Builder $query) => $query->where('code', 'superadmin'))
            ->where('scope_key', 'global')
            ->exists()) {
            return true;
        }

        $code = $permission instanceof PermissionCode ? $permission->value : $permission;

        return $assignments
            ->whereHas('role.permissions', fn (Builder $query) => $query->where('code', $code))
            ->exists();
    }

    private function activeAssignments(
        User $user,
        ?Organization $organization,
    ): Builder {
        $scopeKeys = ['global'];

        if ($organization !== null) {
            $scopeKeys[] = UserRoleAssignment::scopeKey($organization->id);
        }

        return UserRoleAssignment::query()
            ->where('user_id', $user->id)
            ->whereIn('scope_key', $scopeKeys)
            ->where(fn (Builder $query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }
}
