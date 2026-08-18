<?php

namespace App\Services\Inventory;

use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\User;

class InventoryTransferService
{
    public function __construct(
        private readonly InventoryLineResolver $resolver,
        private readonly InventoryLocationResolver $locations,
        private readonly InventoryLotService $lots,
        private readonly InventoryPolicyService $policies,
        private readonly InventoryBackdatePolicy $backdates,
        private readonly InventoryTransferPostingService $posting,
    ) {}

    public function post(
        Organization $organization,
        User $actor,
        string $idempotencyKey,
        string $sourcePublicId,
        string $destinationPublicId,
        array $inputLines,
        ?string $occurredAt = null,
        ?string $reason = null,
    ) {
        if (! $this->policies->for($organization)->allow_immediate_transfers) {
            throw new InventoryDomainException(
                'inventory_immediate_transfer_disabled',
                'Use the controlled stock-transfer workflow for this organization.',
                409,
            );
        }

        if ($sourcePublicId === $destinationPublicId) {
            throw new InventoryDomainException(
                'inventory_transfer_same_location',
                'Source and destination locations must differ.',
            );
        }

        $source = $this->locations->active($organization, $sourcePublicId);
        $destination = $this->locations->active($organization, $destinationPublicId);
        $resolvedLines = [];

        foreach ($inputLines as $input) {
            $resolved = $this->resolver->resolve($organization, $input);
            $lot = $this->lots->find(
                $organization,
                $resolved['product'],
                $input['lot_id'] ?? null,
            );

            $resolvedLines[] = [
                'product_id' => $resolved['product']->id,
                'product_packaging_id' => $resolved['packaging']?->id,
                'inventory_lot_id' => $lot?->id,
                'entered_quantity' => $resolved['entered_quantity'],
                'conversion_to_base_snapshot' => $resolved['conversion_to_base_snapshot'],
                'quantity_base' => $resolved['quantity_base'],
            ];
        }

        return $this->posting->post(
            $organization,
            $actor,
            $idempotencyKey,
            $source,
            $destination,
            $resolvedLines,
            new InventoryPostingContext(
                reason: $reason,
                occurredAt: $this->backdates->occurredAt(
                    $organization,
                    $actor,
                    $occurredAt,
                    $reason,
                ),
            ),
        );
    }
}
