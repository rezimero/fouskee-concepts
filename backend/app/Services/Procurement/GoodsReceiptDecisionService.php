<?php

namespace App\Services\Procurement;

use App\Enums\GoodsReceiptStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\GoodsReceipt;
use App\Models\Organization;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class GoodsReceiptDecisionService
{
    public function __construct(
        private readonly ApprovalLocator $approvalLocator,
        private readonly ApprovalService $approvals,
        private readonly GoodsReceiptPoster $poster,
        private readonly AuditRecorder $audit,
    ) {}

    public function approve(
        Organization $organization,
        User $actor,
        string $publicId,
        string $reason,
    ): GoodsReceipt {
        return DB::transaction(function () use (
            $organization,
            $actor,
            $publicId,
            $reason,
        ) {
            $receipt = $this->locked($organization, $publicId);

            if ($receipt->status === GoodsReceiptStatus::Posted) {
                return $receipt;
            }

            $this->assertPending($receipt);
            $approval = $this->approvalLocator->pending(
                $organization,
                'procurement.goods_receipt',
                $receipt->public_id,
                'over_receipt',
            );
            $this->approvals->approve($approval, $actor, $reason);

            $this->audit->record(
                event: 'procurement.receiving_exception.approved',
                actor: $actor,
                subject: $receipt,
                organizationId: $organization->id,
                reason: $reason,
            );

            return $this->poster->post(
                $organization,
                $actor,
                $receipt,
                $reason,
            );
        }, 3);
    }

    public function reject(
        Organization $organization,
        User $actor,
        string $publicId,
        string $reason,
    ): GoodsReceipt {
        return DB::transaction(function () use (
            $organization,
            $actor,
            $publicId,
            $reason,
        ) {
            $receipt = $this->locked($organization, $publicId);
            $this->assertPending($receipt);
            $approval = $this->approvalLocator->pending(
                $organization,
                'procurement.goods_receipt',
                $receipt->public_id,
                'over_receipt',
            );
            $this->approvals->reject($approval, $actor, $reason);

            $receipt->update([
                'status' => GoodsReceiptStatus::Rejected,
                'rejected_by_user_id' => $actor->id,
                'rejected_at' => now(),
            ]);

            $this->audit->record(
                event: 'procurement.receiving_exception.rejected',
                actor: $actor,
                subject: $receipt,
                organizationId: $organization->id,
                after: ['status' => 'rejected'],
                reason: $reason,
            );

            return $receipt->load($this->relations());
        }, 3);
    }

    private function locked(
        Organization $organization,
        string $publicId,
    ): GoodsReceipt {
        return GoodsReceipt::query()
            ->with($this->relations())
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertPending(GoodsReceipt $receipt): void
    {
        if ($receipt->status !== GoodsReceiptStatus::PendingException) {
            throw new InventoryDomainException(
                'procurement_receipt_not_pending_exception',
                'The goods receipt is not awaiting exception approval.',
                409,
            );
        }
    }

    private function relations(): array
    {
        return [
            'purchaseOrder.supplier',
            'location',
            'lines.product',
            'lines.packaging',
            'movement',
        ];
    }
}
