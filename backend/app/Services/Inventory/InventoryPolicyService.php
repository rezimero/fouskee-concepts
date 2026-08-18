<?php

namespace App\Services\Inventory;

use App\Models\InventoryPolicy;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditRecorder;

class InventoryPolicyService
{
    public function __construct(
        private readonly AuditRecorder $audit,
    ) {}

    public function for(Organization $organization): InventoryPolicy
    {
        return InventoryPolicy::query()->firstOrNew(
            ['organization_id' => $organization->id],
            [
                'count_variance_approval_value_threshold' => 100000,
                'backdate_window_days' => 2,
                'require_separate_transfer_receiver' => true,
                'allow_immediate_transfers' => true,
            ],
        );
    }

    public function update(
        Organization $organization,
        User $actor,
        array $data,
        string $reason,
    ): InventoryPolicy {
        $policy = $this->for($organization);
        $before = $policy->only(array_keys($data));

        $policy->fill($data)->save();

        $this->audit->record(
            event: 'inventory.policy.updated',
            actor: $actor,
            subject: $policy,
            organizationId: $organization->id,
            before: $before,
            after: $policy->only(array_keys($data)),
            reason: $reason,
        );

        return $policy;
    }
}
