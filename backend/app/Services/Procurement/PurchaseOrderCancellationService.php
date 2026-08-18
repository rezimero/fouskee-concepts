<?php

namespace App\Services\Procurement;

use App\Enums\PurchaseOrderStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\GoodsReceipt;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class PurchaseOrderCancellationService
{
    public function __construct(
        private readonly ApprovalLocator $approvalLocator,
        private readonly ApprovalService $approvals,
        private readonly AuditRecorder $audit,
    ) {}

    public function cancel(
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
            $order = PurchaseOrder::query()
                ->with(['supplier', 'lines.product', 'lines.packaging'])
                ->where('organization_id', $organization->id)
                ->where('public_id', $publicId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === PurchaseOrderStatus::Cancelled) {
                return $order;
            }

            if (in_array($order->status, [
                PurchaseOrderStatus::Received,
                PurchaseOrderStatus::Closed,
            ], true)) {
                throw new InventoryDomainException(
                    'procurement_purchase_order_cannot_cancel',
                    'A received or closed purchase order cannot be cancelled.',
                    409,
                );
            }

            if (GoodsReceipt::query()
                ->where('purchase_order_id', $order->id)
                ->where('status', 'posted')
                ->exists()) {
                throw new InventoryDomainException(
                    'procurement_purchase_order_has_receipts',
                    'A purchase order with posted receipts cannot be cancelled.',
                    409,
                );
            }

            $pending = $this->approvalLocator->findPending(
                $organization,
                'procurement.purchase_order',
                $order->public_id,
                'approve',
            );

            if ($pending !== null) {
                $this->approvals->cancel($pending, $actor, $reason);
            }

            $order->update([
                'status' => PurchaseOrderStatus::Cancelled,
                'cancelled_by_user_id' => $actor->id,
                'cancelled_at' => now(),
            ]);

            $this->audit->record(
                event: 'procurement.purchase_order.cancelled',
                actor: $actor,
                subject: $order,
                organizationId: $organization->id,
                after: ['status' => 'cancelled'],
                reason: $reason,
            );

            return $order;
        }, 3);
    }
}
