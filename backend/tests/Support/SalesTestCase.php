<?php

namespace Tests\Support;

use App\Models\Customer;
use App\Models\PriceList;
use App\Models\PriceListItem;

abstract class SalesTestCase extends InventoryTestCase
{
    protected Customer $customer;

    protected PriceList $priceList;

    protected function setUp(): void
    {
        parent::setUp();

        $this->priceList = PriceList::query()->create([
            'organization_id' => $this->organization->id,
            'code' => 'SALES-TEST',
            'name' => 'Sales Test NGN',
            'currency_code' => 'NGN',
            'status' => 'active',
            'effective_from' => today()->subDay(),
            'effective_to' => today()->addYear(),
        ]);

        PriceListItem::query()->create([
            'price_list_id' => $this->priceList->id,
            'product_packaging_id' => $this->packaging->id,
            'selling_price' => 62000,
            'minimum_selling_price' => 59000,
            'maximum_discount_percent' => 4.84,
        ]);

        $this->customer = Customer::query()->create([
            'organization_id' => $this->organization->id,
            'customer_number' => 'CUS-SALES-001',
            'segment' => 'wholesale',
            'display_name' => 'Sales Test Customer',
            'credit_limit' => 500000,
            'default_price_list_id' => $this->priceList->id,
            'status' => 'active',
        ]);
    }

    protected function salesOrder(
        string $key,
        string $settlement = 'prepaid',
        int|float|null $requestedPrice = null,
        int|float $quantity = 1,
    ) {
        $line = [
            'product_id' => $this->product->public_id,
            'packaging_id' => $this->packaging->public_id,
            'quantity' => $quantity,
        ];

        if ($requestedPrice !== null) {
            $line['requested_unit_price'] = $requestedPrice;
        }

        return $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/sales/orders', [
                'customer_id' => $this->customer->public_id,
                'settlement_type' => $settlement,
                'lines' => [$line],
            ]);
    }
}
