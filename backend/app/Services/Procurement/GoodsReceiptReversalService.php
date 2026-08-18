<?php

namespace App\Services\Procurement;

use App\Enums\GoodsReceiptStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\GoodsReceipt;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Inventory\MovementReversalService;
use Illuminate\Support\Facades\DB;

class GoodsReceiptReversalService
{
    public function __construct(
        private readonly MovementReversalService $reversals,
        private readonly PurchaseOrderReceiptStatusService $orderStatus,
        private readonly AuditRecorder $audit,
    ) {}

    public function reverse(
        Organization $organization,
        User $actor,
        string $publicId,
        string $idempotencyKey,
        string $reason,
    ): GoodsReceipt {
        return DB::transaction(function () use (
            $organization,
            $actor,
            $publicId,
            $idempotencyKey,
            $reason,
        ) {
            $receipt = GoodsReceipt::query()
                ->with([
                    'purchaseOrder.lines',
                    'location',
                    'lines.product',
                    'lines.packaging',
                    'movement',
                ])
                ->where('organization_id', $organization->id)
                ->where('public_id', $publicId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($receipt->status === GoodsReceiptStatus::Reversed) {
                return $receipt;
            }

            if (
                $receipt->status !== GoodsReceiptStatus::Posted
                || $receipt->movement === null
            ) {
                throw new InventoryDomainException(
                    'procurement_receipt_not_reversible',
                    'Only a posted goods receipt can be reversed.',
                    409,
                );
            }

            $reversal = $this->reversals->reverseOwned(
                $organization,
                $actor,
                $receipt->movement->public_id,
                $idempotencyKey,
                $reason,
            );

            $receipt->update([
                'status' => GoodsReceiptStatus::Reversed,
                'reversed_by_user_id' => $actor->id,
                'reversal_movement_id' => $reversal->id,
                'reversed_at' => now(),
            ]);

            $this->orderStatus->refresh($receipt->purchaseOrder);

            $this->audit->record(
                event: 'procurement.goods_receipt.reversed',
                actor: $actor,
                subject: $receipt,
                organizationId: $organization->id,
                after: ['reversal_movement_id' => $reversal->public_id],
                reason: $reason,
            );

            return $receipt->refresh()->load([
                'purchaseOrder.supplier',
                'location',
                'lines.product',
                'lines.packaging',
                'movement',
            ]);
        }, 3);
    }
}
