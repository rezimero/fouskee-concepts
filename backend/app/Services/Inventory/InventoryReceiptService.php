<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryReceiptService
{
    public function __construct(
        private readonly InventoryLineResolver $resolver,
        private readonly InventoryLocationResolver $locations,
        private readonly InventoryLotService $lots,
        private readonly InventoryBackdatePolicy $backdates,
        private readonly InventoryPoster $poster,
    ) {}

    public function post(
        Organization $organization,
        User $actor,
        string $idempotencyKey,
        string $locationPublicId,
        array $inputLines,
        ?string $occurredAt = null,
        ?string $reason = null,
    ) {
        return DB::transaction(function () use (
            $organization,
            $actor,
            $idempotencyKey,
            $locationPublicId,
            $inputLines,
            $occurredAt,
            $reason,
        ) {
            $location = $this->locations->active($organization, $locationPublicId);
            $lines = [];

            foreach ($inputLines as $input) {
                $resolved = $this->resolver->resolve($organization, $input);
                $lot = $this->lots->resolveReceipt(
                    $organization,
                    $actor,
                    $resolved['product'],
                    $input,
                );

                $lines[] = [
                    'inventory_location_id' => $location->id,
                    'product_id' => $resolved['product']->id,
                    'product_packaging_id' => $resolved['packaging']?->id,
                    'inventory_lot_id' => $lot?->id,
                    'entered_quantity' => $resolved['entered_quantity'],
                    'conversion_to_base_snapshot' => $resolved['conversion_to_base_snapshot'],
                    'quantity_base' => $resolved['quantity_base'],
                    'unit_cost_snapshot' => $resolved['unit_cost_snapshot'],
                    'inventory_value_delta' => null,
                ];
            }

            return $this->poster->post(
                $organization,
                $actor,
                InventoryMovementType::Receipt,
                $idempotencyKey,
                $lines,
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
        }, 3);
    }
}
