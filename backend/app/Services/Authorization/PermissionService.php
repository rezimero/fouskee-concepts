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

        if ($this->isSuperadmin($user, $organization)) {
            return true;
        }

        $code = $permission instanceof PermissionCode
            ? $permission->value
            : $permission;

        return $this->activeAssignments($user, $organization)
            ->whereHas('role.permissions', fn (Builder $query) => $query
                ->where('code', $code))
            ->exists();
    }

    public function codes(
        User $user,
        ?Organization $organization = null,
    ): array {
        if ($user->status !== UserStatus::Active) {
            return [];
        }

        if ($this->isSuperadmin($user, $organization)) {
            return ['*'];
        }

        return $this->activeAssignments($user, $organization)
            ->with('role.permissions:id,code')
            ->get()
            ->flatMap(fn (UserRoleAssignment $assignment) => $assignment
                ->role
                ->permissions
                ->pluck('code'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function isSuperadmin(
        User $user,
        ?Organization $organization,
    ): bool {
        return (clone $this->activeAssignments($user, $organization))
            ->whereHas('role', fn (Builder $query) => $query
                ->where('code', 'superadmin'))
            ->where('scope_key', 'global')
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
