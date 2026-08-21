<?php

namespace App\Services\Sales;

use App\Enums\SalesFulfillmentStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\SalesFulfillment;
use App\Models\SalesOrder;

class SalesFulfillmentGuard
{
    public function assertReservationReleaseAllowed(SalesOrder $order): void
    {
        if (SalesFulfillment::query()
            ->where('sales_order_id', $order->id)
            ->where('status', SalesFulfillmentStatus::Picked)
            ->exists()) {
            throw new InventoryDomainException(
                'sales_order_pick_in_progress',
                'Picked inventory must be dispatched or the pick must be reversed.',
                409,
            );
        }
    }

    public function assertNoOpenFulfillment(SalesOrder $order): void
    {
        if (SalesFulfillment::query()
            ->where('sales_order_id', $order->id)
            ->whereIn('status', [
                SalesFulfillmentStatus::Draft->value,
                SalesFulfillmentStatus::Picked->value,
                SalesFulfillmentStatus::Dispatched->value,
            ])
            ->exists()) {
            throw new InventoryDomainException(
                'sales_order_open_fulfillment_exists',
                'The order already has an active fulfillment workflow.',
                409,
            );
        }
    }
}
