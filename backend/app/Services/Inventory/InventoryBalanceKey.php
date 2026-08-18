<?php

namespace App\Services\Inventory;

final class InventoryBalanceKey
{
    public function scopeKey(?int $lotId): string
    {
        return $lotId === null ? 'none' : "lot:{$lotId}";
    }

    public function fromLine(array $line): string
    {
        return $line['inventory_location_id'].'-'
            .$line['product_id'].'-'
            .$this->scopeKey($line['inventory_lot_id'] ?? null);
    }
}
