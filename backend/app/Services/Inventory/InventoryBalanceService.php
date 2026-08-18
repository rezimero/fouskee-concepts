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
    ) {}

    public function lock(int $organizationId, int $locationId, int $productId): InventoryBalance
    {
        $scope = [
            'organization_id' => $organizationId,
            'inventory_location_id' => $locationId,
            'product_id' => $productId,
            'lot_scope_key' => 'none',
        ];

        DB::table('inventory_balances')->insertOrIgnore($scope + [
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

    public function assertAvailable(InventoryBalance $balance, string $delta): void
    {
        if (! $this->math->isNegative($delta)) {
            return;
        }

        $reserved = $this->reservedBase(
            $balance->organization_id,
            $balance->inventory_location_id,
            $balance->product_id,
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

    public function apply(InventoryBalance $balance, string $quantityDelta): void
    {
        DB::table('inventory_balances')
            ->where('id', $balance->id)
            ->update([
                'quantity_on_hand_base' => DB::raw(
                    "quantity_on_hand_base + CAST('{$quantityDelta}' AS DECIMAL(30,6))"
                ),
                'version' => DB::raw('version + 1'),
                'projected_at' => now(),
            ]);
    }

    public function reservedBase(
        int $organizationId,
        int $locationId,
        int $productId,
    ): string {
        return (string) InventoryReservation::query()
            ->where('organization_id', $organizationId)
            ->where('inventory_location_id', $locationId)
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->sum('quantity_base');
    }
}
