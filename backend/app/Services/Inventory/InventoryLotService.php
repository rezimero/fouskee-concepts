<?php

namespace App\Services\Inventory;

use App\Exceptions\InventoryDomainException;
use App\Models\InventoryLot;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use App\Services\Audit\AuditRecorder;

class InventoryLotService
{
    public function __construct(
        private readonly AuditRecorder $audit,
    ) {}

    public function find(
        Organization $organization,
        Product $product,
        ?string $publicId,
    ): ?InventoryLot {
        if ($publicId === null) {
            return null;
        }

        return InventoryLot::query()
            ->where('organization_id', $organization->id)
            ->where('product_id', $product->id)
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    public function resolveReceipt(
        Organization $organization,
        User $actor,
        Product $product,
        array $data,
    ): ?InventoryLot {
        if (! empty($data['lot_id'])) {
            return $this->find($organization, $product, $data['lot_id']);
        }

        $code = trim((string) ($data['lot_code'] ?? ''));

        if ($code === '') {
            return null;
        }

        $lot = InventoryLot::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'product_id' => $product->id,
                'code' => $code,
            ],
            [
                'received_at' => now(),
                'expiry_date' => $data['expiry_date'] ?? null,
                'status' => 'active',
            ],
        );

        if (
            ! $lot->wasRecentlyCreated
            && isset($data['expiry_date'])
            && optional($lot->expiry_date)->toDateString() !== $data['expiry_date']
        ) {
            throw new InventoryDomainException(
                'inventory_lot_expiry_conflict',
                'The lot already exists with a different expiry date.',
                409,
            );
        }

        if ($lot->wasRecentlyCreated) {
            $this->audit->record(
                event: 'inventory.lot.created',
                actor: $actor,
                subject: $lot,
                organizationId: $organization->id,
                after: ['code' => $lot->code],
            );
        }

        return $lot;
    }
}
