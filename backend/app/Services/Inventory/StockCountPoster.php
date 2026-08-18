<?php

namespace App\Services\Inventory;

use App\Models\Organization;
use App\Models\StockCountSession;
use App\Models\User;

class StockCountPoster
{
    public function __construct(
        private readonly InventoryAdjustmentService $adjustments,
        private readonly InventoryQuantityMath $math,
    ) {}

    public function post(
        Organization $organization,
        User $actor,
        StockCountSession $session,
        string $idempotencyKey,
        string $reason,
    ) {
        $lines = $session->lines
            ->reject(fn ($line) => $this->math->isZero($line->variance_quantity_base))
            ->map(fn ($line) => [
                'product_id' => $line->product->public_id,
                'lot_id' => $line->lot?->public_id,
                'quantity_delta' => $line->variance_quantity_base,
                'unit_cost_base' => $line->unit_cost_snapshot,
            ])
            ->values()
            ->all();

        if ($lines === []) {
            return null;
        }

        return $this->adjustments->post(
            $organization,
            $actor,
            $idempotencyKey,
            $session->location->public_id,
            $lines,
            $reason,
            sourceType: 'stock_count',
            sourcePublicId: $session->public_id,
        );
    }
}
