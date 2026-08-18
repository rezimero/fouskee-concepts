<?php

namespace App\Services\Inventory;

class InventoryBalanceCoordinator
{
    public function __construct(
        private readonly InventoryBalanceService $balances,
        private readonly InventoryBalanceKey $keys,
        private readonly InventoryQuantityMath $math,
    ) {}

    public function lockAll(int $organizationId, array $lines): array
    {
        $ordered = collect($lines)
            ->sortBy(fn (array $line) => $this->keys->fromLine($line))
            ->values();

        $locked = [];

        foreach ($ordered as $line) {
            $key = $this->keys->fromLine($line);

            if (isset($locked[$key])) {
                continue;
            }

            $locked[$key] = $this->balances->lock(
                $organizationId,
                $line['inventory_location_id'],
                $line['product_id'],
                $line['inventory_lot_id'] ?? null,
            );
        }

        return $locked;
    }

    public function assertAvailability(array $lines, array $locked): void
    {
        foreach ($this->aggregate($lines, 'quantity_base') as $key => $delta) {
            $this->balances->assertAvailable($locked[$key], $delta);
        }
    }

    public function aggregate(array $lines, string $field): array
    {
        $totals = [];

        foreach ($lines as $line) {
            $key = $this->keys->fromLine($line);
            $value = (string) ($line[$field] ?? 0);

            $totals[$key] = isset($totals[$key])
                ? $this->math->add($totals[$key], $value)
                : $value;
        }

        return $totals;
    }
}
