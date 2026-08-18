<?php

namespace App\Services\Inventory;

use App\Enums\PermissionCode;
use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\PermissionService;
use Carbon\CarbonImmutable;

class InventoryBackdatePolicy
{
    public function __construct(
        private readonly InventoryPolicyService $policies,
        private readonly PermissionService $permissions,
    ) {}

    public function occurredAt(
        Organization $organization,
        User $actor,
        ?string $value,
        ?string $reason,
    ): ?CarbonImmutable {
        if ($value === null) {
            return null;
        }

        $occurredAt = CarbonImmutable::parse($value);
        $window = $this->policies->for($organization)->backdate_window_days;
        $cutoff = CarbonImmutable::now()->subDays($window);

        if ($occurredAt->greaterThanOrEqualTo($cutoff)) {
            return $occurredAt;
        }

        if (! $this->permissions->allows(
            $actor,
            PermissionCode::InventoryBackdate,
            $organization,
        )) {
            throw new InventoryDomainException(
                'inventory_backdate_not_permitted',
                'This posting date is outside the permitted backdate window.',
                403,
            );
        }

        if (mb_strlen(trim((string) $reason)) < 10) {
            throw new InventoryDomainException(
                'inventory_backdate_reason_required',
                'A privileged business reason is required for material backdating.',
                422,
            );
        }

        return $occurredAt;
    }
}
