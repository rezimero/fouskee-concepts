<?php

namespace App\Services\Authorization;

use App\Enums\RoleScope;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleAssignment;
use InvalidArgumentException;

class RoleAssignmentService
{
    public function assign(
        User $user,
        Role $role,
        ?Organization $organization = null,
        ?User $assignedBy = null,
    ): UserRoleAssignment {
        $this->assertScope($role, $organization);

        return UserRoleAssignment::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'scope_key' => UserRoleAssignment::scopeKey($organization?->id),
            ],
            [
                'organization_id' => $organization?->id,
                'assigned_by' => $assignedBy?->id,
                'assigned_at' => now(),
            ],
        );
    }

    private function assertScope(Role $role, ?Organization $organization): void
    {
        if ($role->scope === RoleScope::Global && $organization !== null) {
            throw new InvalidArgumentException('Global roles cannot be organization scoped.');
        }

        if ($role->scope === RoleScope::Organization && $organization === null) {
            throw new InvalidArgumentException('Organization roles require an organization.');
        }
    }
}
