<?php

namespace App\Services\Sales;

use App\Models\SalesOrder;
use App\Services\Support\DecimalMath;

class SalesOrderReservationRequirements
{
    public function __construct(private readonly DecimalMath $math) {}

    public function for(SalesOrder $order): array
    {
        $requirements = [];

        foreach ($order->lines as $line) {
            $productId = $line->product_id;

            $requirements[$productId] ??= [
                'product' => $line->product,
                'quantity_base' => '0.000000',
            ];

            $requirements[$productId]['quantity_base'] = $this->math->add(
                $requirements[$productId]['quantity_base'],
                (string) $line->ordered_quantity_base,
            );
        }

        ksort($requirements);

        return $requirements;
    }
}
