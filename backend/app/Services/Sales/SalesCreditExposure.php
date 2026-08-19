<?php

namespace App\Services\Sales;

use App\Enums\SalesOrderStatus;
use App\Enums\SalesSettlementType;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Services\Support\DecimalMath;

class SalesCreditExposure
{
    public function __construct(private readonly DecimalMath $math) {}

    public function snapshot(Customer $customer, SalesOrder $order): array
    {
        if ($order->settlement_type !== SalesSettlementType::Credit) {
            return [
                'limit' => (string) $customer->credit_limit,
                'before' => '0.000000',
                'over_limit' => false,
            ];
        }

        $before = (string) SalesOrder::query()
            ->where('organization_id', $customer->organization_id)
            ->where('customer_id', $customer->id)
            ->where('settlement_type', SalesSettlementType::Credit->value)
            ->whereIn('status', [
                SalesOrderStatus::Submitted->value,
                SalesOrderStatus::Approved->value,
            ])
            ->whereKeyNot($order->id)
            ->sum('total_amount');

        $after = $this->math->add($before, (string) $order->total_amount);
        $limit = (string) $customer->credit_limit;

        return [
            'limit' => $limit,
            'before' => $before,
            'over_limit' => $this->math->greaterThan($after, $limit),
        ];
    }
}
