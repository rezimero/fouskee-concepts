<?php

namespace App\Services\Inventory;

use App\Exceptions\InventoryDomainException;
use App\Models\InventoryBalance;
use App\Models\InventoryReservation;
use Illuminate\Support\Facades\DB;

class InventoryBalanceService
{
    public function __construct(
        private readonly InventoryQuantityMath $math,
        private readonly InventoryBalanceKey $keys,
    ) {}

    public function lock(
        int $organizationId,
        int $locationId,
        int $productId,
        ?int $lotId = null,
    ): InventoryBalance {
        $scope = [
            'organization_id' => $organizationId,
            'inventory_location_id' => $locationId,
            'product_id' => $productId,
            'lot_scope_key' => $this->keys->scopeKey($lotId),
        ];

        DB::table('inventory_balances')->insertOrIgnore($scope + [
            'inventory_lot_id' => $lotId,
            'quantity_on_hand_base' => 0,
            'inventory_value' => 0,
            'weighted_average_unit_cost' => 0,
            'version' => 0,
        ]);

        return InventoryBalance::query()
            ->where($scope)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function assertAvailable(
        InventoryBalance $balance,
        string $delta,
    ): void {
        if (! $this->math->isNegative($delta)) {
            return;
        }

        $reserved = $this->reservedBase(
            $balance->organization_id,
            $balance->inventory_location_id,
            $balance->product_id,
            $balance->inventory_lot_id,
        );

        $available = $this->math->add(
            $balance->quantity_on_hand_base,
            $this->math->negate($reserved),
        );
        $next = $this->math->add($available, $delta);

        if ($this->math->isNegative($next)) {
            throw new InventoryDomainException(
                'inventory_insufficient_stock',
                'The movement exceeds available stock.',
                409,
            );
        }
    }

    public function reservedBase(
        int $organizationId,
        int $locationId,
        int $productId,
        ?int $lotId = null,
    ): string {
        return (string) InventoryReservation::query()
            ->where('organization_id', $organizationId)
            ->where('inventory_location_id', $locationId)
            ->where('product_id', $productId)
            ->where('inventory_lot_id', $lotId)
            ->where('status', 'active')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->sum('quantity_base');
    }
}
