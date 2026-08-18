<?php

namespace App\Services\Procurement;

use App\Enums\PurchaseOrderStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use App\Services\Audit\AuditRecorder;
use App\Services\Support\DecimalMath;
use Illuminate\Support\Facades\DB;

class PurchaseOrderSubmitter
{
    public function __construct(
        private readonly ProcurementPolicyService $policies,
        private readonly ApprovalService $approvals,
        private readonly DecimalMath $math,
        private readonly AuditRecorder $audit,
    ) {}

    public function submit(
        Organization $organization,
        User $actor,
        string $publicId,
    ): PurchaseOrder {
        return DB::transaction(function () use ($organization, $actor, $publicId) {
            $order = $this->locked($organization, $publicId);

            if (in_array($order->status, [
                PurchaseOrderStatus::Submitted,
                PurchaseOrderStatus::Approved,
            ], true)) {
                return $order;
            }

            if ($order->status !== PurchaseOrderStatus::Draft) {
                throw new InventoryDomainException(
                    'procurement_purchase_order_not_draft',
                    'Only a draft purchase order can be submitted.',
                    409,
                );
            }

            $policy = $this->policies->current($organization);
            $requiresApproval = $this->math->greaterThan(
                $order->total_amount,
                $policy->purchase_order_approval_value_threshold,
            );

            $order->update([
                'status' => $requiresApproval
                    ? PurchaseOrderStatus::Submitted
                    : PurchaseOrderStatus::Approved,
                'policy_version_id' => $policy->id,
                'submitted_by_user_id' => $actor->id,
                'submitted_at' => now(),
                'approved_by_user_id' => $requiresApproval ? null : $actor->id,
                'approved_at' => $requiresApproval ? null : now(),
            ]);

            if ($requiresApproval) {
                $this->approvals->request(
                    $organization,
                    $actor,
                    'procurement.purchase_order',
                    $order->public_id,
                    'approve',
                    [
                        'total_amount' => $order->total_amount,
                        'threshold' => $policy->purchase_order_approval_value_threshold,
                    ],
                );
            }

            $this->audit->record(
                event: 'procurement.purchase_order.submitted',
                actor: $actor,
                subject: $order,
                organizationId: $organization->id,
                after: ['status' => $order->status->value],
            );

            if (! $requiresApproval) {
                $this->audit->record(
                    event: 'procurement.purchase_order.auto_approved',
                    actor: $actor,
                    subject: $order,
                    organizationId: $organization->id,
                    after: ['status' => 'approved'],
                    reason: 'Below configured maker-checker threshold',
                );
            }

            return $order->load($this->relations());
        }, 3);
    }

    private function locked(
        Organization $organization,
        string $publicId,
    ): PurchaseOrder {
        return PurchaseOrder::query()
            ->with($this->relations())
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function relations(): array
    {
        return ['supplier', 'lines.product', 'lines.packaging', 'policyVersion'];
    }
}
