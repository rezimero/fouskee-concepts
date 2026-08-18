<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\InventoryDomainException;
use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\User;

class InventoryTransferService
{
    public function __construct(
        private readonly InventoryLineResolver $resolver,
        private readonly InventoryPoster $poster,
        private readonly InventoryQuantityMath $math,
    ) {}

    public function post(
        Organization $organization,
        User $actor,
        string $idempotencyKey,
        string $sourcePublicId,
        string $destinationPublicId,
        array $inputLines,
    ) {
        if ($sourcePublicId === $destinationPublicId) {
            throw new InventoryDomainException(
                'inventory_transfer_same_location',
                'Source and destination locations must differ.',
            );
        }

        $locations = InventoryLocation::query()
            ->where('organization_id', $organization->id)
            ->whereIn('public_id', [$sourcePublicId, $destinationPublicId])
            ->get()
            ->keyBy('public_id');

        $source = $locations->get($sourcePublicId);
        $destination = $locations->get($destinationPublicId);

        abort_if($source === null || $destination === null, 404);

        $lines = [];
        foreach ($inputLines as $input) {
            $resolved = $this->resolver->resolve($organization, $input);
            $base = $resolved['quantity_base'];
            $common = [
                'product_id' => $resolved['product']->id,
                'product_packaging_id' => $resolved['packaging']?->id,
                'inventory_lot_id' => null,
                'entered_quantity' => $resolved['entered_quantity'],
                'conversion_to_base_snapshot' => $resolved['conversion_to_base_snapshot'],
                'unit_cost_snapshot' => null,
                'inventory_value_delta' => null,
            ];

            $lines[] = $common + [
                'inventory_location_id' => $source->id,
                'quantity_base' => $this->math->negate($base),
            ];
            $lines[] = $common + [
                'inventory_location_id' => $destination->id,
                'quantity_base' => $base,
            ];
        }

        return $this->poster->post(
            $organization,
            $actor,
            InventoryMovementType::Transfer,
            $idempotencyKey,
            $lines,
        );
    }
}
