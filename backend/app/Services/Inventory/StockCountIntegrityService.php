<?php

namespace App\Services\Inventory;

use App\Exceptions\InventoryDomainException;
use App\Models\StockCountSession;

class StockCountIntegrityService
{
    public function __construct(
        private readonly InventoryBalanceService $balances,
        private readonly InventoryQuantityMath $math,
    ) {}

    public function assertFresh(StockCountSession $session): void
    {
        foreach ($session->lines as $line) {
            $balance = $this->balances->lock(
                $session->organization_id,
                $session->inventory_location_id,
                $line->product_id,
                $line->inventory_lot_id,
            );

            $quantityChanged = ! $this->math->isZero($this->math->add(
                $balance->quantity_on_hand_base,
                $this->math->negate($line->system_quantity_base),
            ));
            $costChanged = $this->math->isPositive($line->system_quantity_base)
                && ! $this->math->isZero($this->math->add(
                    $balance->weighted_average_unit_cost,
                    $this->math->negate($line->unit_cost_snapshot),
                ));

            if (
                $balance->version !== $line->balance_version_snapshot
                || $quantityChanged
                || $costChanged
            ) {
                throw new InventoryDomainException(
                    'inventory_count_stale',
                    'Stock changed after the count snapshot. Start a new count.',
                    409,
                );
            }
        }
    }
}
