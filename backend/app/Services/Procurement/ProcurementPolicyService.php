<?php

namespace App\Services\Procurement;

use App\Models\Organization;
use App\Models\ProcurementPolicyVersion;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class ProcurementPolicyService
{
    public function __construct(
        private readonly AuditRecorder $audit,
    ) {}

    public function current(Organization $organization): ProcurementPolicyVersion
    {
        return DB::transaction(function () use ($organization) {
            Organization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            $current = ProcurementPolicyVersion::query()
                ->where('organization_id', $organization->id)
                ->where('effective_from', '<=', now())
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first();

            return $current ?? ProcurementPolicyVersion::query()->create([
                'organization_id' => $organization->id,
                'effective_from' => now(),
                'purchase_order_approval_value_threshold' => 0,
                'over_receipt_tolerance_percent' => 0,
                'invoice_quantity_tolerance_percent' => 0,
                'invoice_price_tolerance_percent' => 0,
                'invoice_amount_tolerance_percent' => 0,
            ]);
        }, 3);
    }

    public function createVersion(
        Organization $organization,
        User $actor,
        array $data,
        string $reason,
    ): ProcurementPolicyVersion {
        $policy = ProcurementPolicyVersion::query()->create([
            'organization_id' => $organization->id,
            'effective_from' => now(),
            ...$data,
            'created_by_user_id' => $actor->id,
            'reason' => $reason,
        ]);

        $this->audit->record(
            event: 'procurement.policy.version_created',
            actor: $actor,
            subject: $policy,
            organizationId: $organization->id,
            after: $policy->only([
                'purchase_order_approval_value_threshold',
                'over_receipt_tolerance_percent',
                'invoice_quantity_tolerance_percent',
                'invoice_price_tolerance_percent',
                'invoice_amount_tolerance_percent',
            ]),
            reason: $reason,
        );

        return $policy;
    }
}
