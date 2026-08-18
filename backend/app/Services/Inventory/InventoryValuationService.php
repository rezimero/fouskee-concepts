<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\InventoryDomainException;

class InventoryValuationService
{
    public function __construct(
        private readonly InventoryBalanceKey $keys,
        private readonly InventoryQuantityMath $math,
    ) {}

    public function value(
        InventoryMovementType $type,
        array $lines,
        array $locked,
    ): array {
        if ($type === InventoryMovementType::Reversal) {
            return $this->reversal($lines);
        }

        if ($type === InventoryMovementType::Transfer) {
            return $this->transfer($lines, $locked);
        }

        return array_map(
            fn (array $line) => $this->single($type, $line, $locked),
            $lines,
        );
    }

    private function single(
        InventoryMovementType $type,
        array $line,
        array $locked,
    ): array {
        $balance = $locked[$this->keys->fromLine($line)];
        $quantity = (string) $line['quantity_base'];

        if ($this->math->isNegative($quantity)) {
            $cost = $balance->weighted_average_unit_cost;
        } else {
            $cost = $line['unit_cost_snapshot']
                ?? $balance->weighted_average_unit_cost;

            if (
                $type === InventoryMovementType::Receipt
                && $line['unit_cost_snapshot'] === null
            ) {
                throw $this->costRequired();
            }

            if ($this->math->isZero($cost) && ! $this->math->isZero($quantity)) {
                throw $this->costRequired();
            }
        }

        $line['unit_cost_snapshot'] = (string) $cost;
        $line['inventory_value_delta'] = $this->math->multiply($quantity, $cost);

        return $line;
    }

    private function transfer(array $lines, array $locked): array
    {
        if (count($lines) % 2 !== 0) {
            throw new InventoryDomainException(
                'inventory_transfer_unbalanced',
                'Transfer valuation requires paired source and destination lines.',
                409,
            );
        }

        $valued = [];

        foreach (array_chunk($lines, 2) as [$source, $destination]) {
            $this->assertTransferPair($source, $destination);

            $sourceBalance = $locked[$this->keys->fromLine($source)];
            $cost = (string) $sourceBalance->weighted_average_unit_cost;
            $sourceValue = $this->math->multiply($source['quantity_base'], $cost);

            $source['unit_cost_snapshot'] = $cost;
            $source['inventory_value_delta'] = $sourceValue;
            $destination['unit_cost_snapshot'] = $cost;
            $destination['inventory_value_delta'] = $this->math->negate($sourceValue);

            $valued[] = $source;
            $valued[] = $destination;
        }

        return $valued;
    }

    private function assertTransferPair(array $source, array $destination): void
    {
        $sameIdentity = $source['product_id'] === $destination['product_id']
            && ($source['inventory_lot_id'] ?? null)
                === ($destination['inventory_lot_id'] ?? null);
        $balancedQuantity = $this->math->isZero($this->math->add(
            $source['quantity_base'],
            $destination['quantity_base'],
        ));

        if (
            ! $sameIdentity
            || ! $balancedQuantity
            || ! $this->math->isNegative($source['quantity_base'])
            || ! $this->math->isPositive($destination['quantity_base'])
        ) {
            throw new InventoryDomainException(
                'inventory_transfer_unbalanced',
                'Transfer source and destination legs must exactly balance.',
                409,
            );
        }
    }

    private function reversal(array $lines): array
    {
        return array_map(function (array $line): array {
            $line['inventory_value_delta'] ??= '0.000000';
            $line['unit_cost_snapshot'] ??= '0.000000';

            return $line;
        }, $lines);
    }

    private function costRequired(): InventoryDomainException
    {
        return new InventoryDomainException(
            'inventory_unit_cost_required',
            'A unit cost is required to establish inventory value.',
            422,
        );
    }
}
