<?php

namespace App\Services\Procurement;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\GoodsReceipt;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\Inventory\InventoryBackdatePolicy;
use App\Services\Inventory\InventoryLocationResolver;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class GoodsReceiptCreator
{
    public function __construct(
        private readonly ProcurementIdempotencyGuard $idempotency,
        private readonly ProcurementReferenceNumber $references,
        private readonly ProcurementPolicyService $policies,
        private readonly GoodsReceiptLineAssembler $lines,
        private readonly GoodsReceiptToleranceService $tolerances,
        private readonly InventoryLocationResolver $locations,
        private readonly InventoryBackdatePolicy $backdates,
        private readonly GoodsReceiptPoster $poster,
        private readonly GoodsReceiptExceptionRequester $exceptions,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        string $key,
        array $data,
        ?string $reason = null,
    ): GoodsReceipt {
        $fingerprint = $this->idempotency->fingerprint($data);

        try {
            return DB::transaction(function () use (
                $organization, $actor, $key, $data, $reason, $fingerprint,
            ) {
                $existing = $this->idempotency->existing(
                    GoodsReceipt::class,
                    $organization,
                    $key,
                    $fingerprint,
                );

                if ($existing !== null) {
                    return $existing->load($this->relations());
                }

                $order = $this->lockOrder($organization, $data['purchase_order_id']);
                $this->assertReceivable($order);
                $this->assertNoPendingException($order);

                $location = $this->locations->active(
                    $organization,
                    $data['location_id'],
                );
                $policy = $this->policies->current($organization);
                $receivedAt = $this->backdates->occurredAt(
                    $organization,
                    $actor,
                    $data['received_at'] ?? null,
                    $reason,
                ) ?? now();
                $lines = $this->lines->make($order, $data['lines']);
                $exception = $this->tolerances->exceeds($lines, $policy);

                $receipt = GoodsReceipt::query()->create([
                    'organization_id' => $organization->id,
                    'goods_receipt_number' => $this->references->goodsReceipt($organization),
                    'purchase_order_id' => $order->id,
                    'inventory_location_id' => $location->id,
                    'status' => $exception
                        ? GoodsReceiptStatus::PendingException
                        : GoodsReceiptStatus::PendingPosting,
                    'received_at' => $receivedAt,
                    'notes' => $data['notes'] ?? null,
                    'idempotency_key' => $key,
                    'idempotency_fingerprint' => $fingerprint,
                    'policy_version_id' => $policy->id,
                    'recorded_by_user_id' => $actor->id,
                ]);

                foreach ($lines as $line) {
                    unset($line['purchase_order_line']);
                    $receipt->lines()->create($line);
                }

                if ($exception) {
                    $this->exceptions->request(
                        $organization,
                        $actor,
                        $receipt,
                        $policy->over_receipt_tolerance_percent,
                    );

                    return $receipt->load($this->relations());
                }

                return $this->poster->post(
                    $organization,
                    $actor,
                    $receipt,
                    $reason,
                );
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }

            return $this->idempotency->existing(
                GoodsReceipt::class,
                $organization,
                $key,
                $fingerprint,
            )?->load($this->relations()) ?? throw $exception;
        }
    }

    private function lockOrder(
        Organization $organization,
        string $publicId,
    ): PurchaseOrder {
        return PurchaseOrder::query()
            ->with(['lines.product', 'lines.packaging', 'supplier'])
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertReceivable(PurchaseOrder $order): void
    {
        if (! in_array($order->status, [
            PurchaseOrderStatus::Approved,
            PurchaseOrderStatus::PartiallyReceived,
        ], true)) {
            throw new InventoryDomainException(
                'procurement_purchase_order_not_receivable',
                'Only an approved purchase order can receive goods.',
                409,
            );
        }
    }

    private function assertNoPendingException(PurchaseOrder $order): void
    {
        if (GoodsReceipt::query()
            ->where('purchase_order_id', $order->id)
            ->where('status', GoodsReceiptStatus::PendingException)
            ->exists()) {
            throw new InventoryDomainException(
                'procurement_receipt_exception_pending',
                'Resolve the existing receiving exception first.',
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
            'lines.purchaseOrderLine',
            'movement',
        ];
    }
}
