<?php

namespace App\Services\Sales;

use App\Models\InventoryReservation;
use App\Models\SalesOrder;
use App\Services\Support\DecimalMath;

class SalesOrderReservationSummary
{
    public function __construct(private readonly DecimalMath $math) {}

    public function for(SalesOrder $order): array
    {
        $reservations = InventoryReservation::query()
            ->with(['location', 'product', 'lot'])
            ->where('organization_id', $order->organization_id)
            ->where('source_reference_type', SalesReservationSource::ORDER)
            ->where('source_reference_public_id', $order->public_id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        if ($reservations->isEmpty()) {
            return $this->empty($order);
        }

        $first = $reservations->first();
        $total = '0.000000';

        foreach ($reservations as $reservation) {
            $total = $this->math->add(
                $total,
                (string) $reservation->quantity_base,
            );
        }

        $products = $reservations
            ->groupBy('product_id')
            ->map(function ($group): array {
                $first = $group->first();
                $quantity = '0.000000';

                foreach ($group as $reservation) {
                    $quantity = $this->math->add(
                        $quantity,
                        (string) $reservation->quantity_base,
                    );
                }

                return [
                    'id' => $first->product->public_id,
                    'sku' => $first->product->sku,
                    'name' => $first->product->name,
                    'reserved_base' => $quantity,
                ];
            })
            ->values()
            ->all();

        return [
            'order_id' => $order->public_id,
            'order_number' => $order->sales_order_number,
            'status' => 'active',
            'location' => [
                'id' => $first->location->public_id,
                'code' => $first->location->code,
                'name' => $first->location->name,
            ],
            'total_reserved_base' => $total,
            'reservation_count' => $reservations->count(),
            'products' => $products,
            'reservations' => $reservations->map(fn ($reservation) => [
                'id' => $reservation->public_id,
                'product_id' => $reservation->product->public_id,
                'lot_id' => $reservation->lot?->public_id,
                'lot_code' => $reservation->lot?->code,
                'quantity_base' => $reservation->quantity_base,
            ])->values()->all(),
        ];
    }

    private function empty(SalesOrder $order): array
    {
        return [
            'order_id' => $order->public_id,
            'order_number' => $order->sales_order_number,
            'status' => 'none',
            'location' => null,
            'total_reserved_base' => '0.000000',
            'reservation_count' => 0,
            'products' => [],
            'reservations' => [],
        ];
    }
}
