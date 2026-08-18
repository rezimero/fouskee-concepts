<?php

namespace App\Services\Procurement;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Inventory\InventoryReceiptService;

class GoodsReceiptPoster
{
    public function __construct(
        private readonly InventoryReceiptService $inventory,
        private readonly PurchaseOrderReceiptStatusService $orderStatus,
        private readonly AuditRecorder $audit,
    ) {}

    public function post(
        Organization $organization,
        User $actor,
        GoodsReceipt $receipt,
        ?string $reason = null,
    ): GoodsReceipt {
        $receipt->loadMissing([
            'location',
            'purchaseOrder.lines',
            'lines.product',
            'lines.packaging',
        ]);

        $movement = $this->inventory->post(
            $organization,
            $actor,
            "proc-gr-{$receipt->public_id}",
            $receipt->location->public_id,
            $this->inventoryLines($receipt),
            $receipt->received_at->toISOString(),
            $reason,
            'procurement.goods_receipt',
            $receipt->public_id,
            true,
        );

        $receipt->update([
            'status' => GoodsReceiptStatus::Posted,
            'posted_by_user_id' => $actor->id,
            'inventory_movement_id' => $movement->id,
            'posted_at' => now(),
        ]);

        $this->orderStatus->refresh($receipt->purchaseOrder);

        $this->audit->record(
            event: 'procurement.goods_receipt.posted',
            actor: $actor,
            subject: $receipt,
            organizationId: $organization->id,
            after: [
                'goods_receipt_number' => $receipt->goods_receipt_number,
                'movement_id' => $movement->public_id,
                'purchase_order_id' => $receipt->purchaseOrder->public_id,
            ],
            reason: $reason,
        );

        return $receipt->refresh()->load($this->relations());
    }

    private function inventoryLines(GoodsReceipt $receipt): array
    {
        return $receipt->lines->map(fn ($line) => [
            'product_id' => $line->product->public_id,
            'packaging_id' => $line->packaging?->public_id,
            'quantity' => $line->received_quantity,
            'unit_cost_base' => $line->unit_cost_base_snapshot,
            'lot_code' => $line->lot_code,
            'expiry_date' => $line->expiry_date?->toDateString(),
        ])->all();
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
