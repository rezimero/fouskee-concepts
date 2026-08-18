<?php

namespace App\Services\Procurement;

use App\Enums\PurchaseOrderStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class PurchaseOrderDecisionService
{
    public function __construct(
        private readonly ApprovalLocator $approvalLocator,
        private readonly ApprovalService $approvals,
        private readonly AuditRecorder $audit,
    ) {}

    public function approve(
        Organization $organization,
        User $actor,
        string $publicId,
        string $reason,
    ): PurchaseOrder {
        return DB::transaction(function () use (
            $organization,
            $actor,
            $publicId,
            $reason,
        ) {
            $order = $this->locked($organization, $publicId);

            if ($order->status === PurchaseOrderStatus::Approved) {
                return $order;
            }

            $this->assertSubmitted($order);
            $approval = $this->approvalLocator->pending(
                $organization,
                'procurement.purchase_order',
                $order->public_id,
                'approve',
            );
            $this->approvals->approve($approval, $actor, $reason);

            $order->update([
                'status' => PurchaseOrderStatus::Approved,
                'approved_by_user_id' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->record(
                $order,
                $actor,
                'procurement.purchase_order.approved',
                $reason,
            );

            return $order->load($this->relations());
        }, 3);
    }

    public function reject(
        Organization $organization,
        User $actor,
        string $publicId,
        string $reason,
    ): PurchaseOrder {
        return DB::transaction(function () use (
            $organization,
            $actor,
            $publicId,
            $reason,
        ) {
            $order = $this->locked($organization, $publicId);
            $this->assertSubmitted($order);
            $approval = $this->approvalLocator->pending(
                $organization,
                'procurement.purchase_order',
                $order->public_id,
                'approve',
            );
            $this->approvals->reject($approval, $actor, $reason);

            $order->update([
                'status' => PurchaseOrderStatus::Cancelled,
                'cancelled_by_user_id' => $actor->id,
                'cancelled_at' => now(),
            ]);

            $this->record(
                $order,
                $actor,
                'procurement.purchase_order.rejected',
                $reason,
            );

            return $order->load($this->relations());
        }, 3);
    }

    private function assertSubmitted(PurchaseOrder $order): void
    {
        if ($order->status !== PurchaseOrderStatus::Submitted) {
            throw new InventoryDomainException(
                'procurement_purchase_order_not_submitted',
                'The purchase order is not awaiting approval.',
                409,
            );
        }
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

    private function record(
        PurchaseOrder $order,
        User $actor,
        string $event,
        string $reason,
    ): void {
        $this->audit->record(
            event: $event,
            actor: $actor,
            subject: $order,
            organizationId: $order->organization_id,
            after: ['status' => $order->status->value],
            reason: $reason,
        );
    }

    private function relations(): array
    {
        return ['supplier', 'lines.product', 'lines.packaging', 'policyVersion'];
    }
}
