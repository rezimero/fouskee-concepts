<?php

namespace App\Services\Inventory;

use App\Models\Organization;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class InventoryProjectionRebuilder
{
    public function __construct(
        private readonly InventoryProjectionSnapshot $snapshots,
        private readonly InventoryQuantityMath $math,
        private readonly InventoryBalanceKey $keys,
        private readonly AuditRecorder $audit,
    ) {}

    public function differences(Organization $organization): array
    {
        $ledger = $this->snapshots->ledger($organization->id);
        $projection = $this->snapshots->projection($organization->id);
        $keys = array_unique([...array_keys($ledger), ...array_keys($projection)]);
        $differences = [];

        foreach ($keys as $key) {
            $ledgerQty = (string) ($ledger[$key]->quantity_on_hand_base ?? 0);
            $projectedQty = (string) ($projection[$key]->quantity_on_hand_base ?? 0);
            $ledgerValue = (string) ($ledger[$key]->inventory_value ?? 0);
            $projectedValue = (string) ($projection[$key]->inventory_value ?? 0);

            if (
                ! $this->math->isZero($this->math->add(
                    $ledgerQty,
                    $this->math->negate($projectedQty),
                ))
                || ! $this->math->isZero($this->math->add(
                    $ledgerValue,
                    $this->math->negate($projectedValue),
                ))
            ) {
                $differences[] = compact(
                    'key',
                    'ledgerQty',
                    'projectedQty',
                    'ledgerValue',
                    'projectedValue',
                );
            }
        }

        return $differences;
    }

    public function rebuild(Organization $organization): void
    {
        DB::transaction(function () use ($organization): void {
            $ledger = $this->snapshots->ledger($organization->id);

            DB::table('inventory_balances')
                ->where('organization_id', $organization->id)
                ->delete();

            foreach ($ledger as $row) {
                $quantity = (string) $row->quantity_on_hand_base;
                $value = (string) $row->inventory_value;
                $average = $this->math->isPositive($quantity)
                    ? $this->math->divide($value, $quantity)
                    : '0.000000';

                DB::table('inventory_balances')->insert([
                    'organization_id' => $organization->id,
                    'inventory_location_id' => $row->inventory_location_id,
                    'product_id' => $row->product_id,
                    'inventory_lot_id' => $row->inventory_lot_id,
                    'lot_scope_key' => $this->keys->scopeKey($row->inventory_lot_id),
                    'quantity_on_hand_base' => $quantity,
                    'inventory_value' => $value,
                    'weighted_average_unit_cost' => $average,
                    'version' => 1,
                    'projected_at' => now(),
                ]);
            }

            $this->audit->record(
                event: 'inventory.projection.rebuilt',
                organizationId: $organization->id,
                context: ['row_count' => count($ledger)],
                reason: 'Controlled projection rebuild from immutable ledger',
            );
        });
    }
}
