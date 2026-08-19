<?php

namespace App\Services\Sales;

use App\Exceptions\InventoryDomainException;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\ProductPackaging;
use App\Services\Support\DecimalMath;

class SalesPriceResolver
{
    public function __construct(private readonly DecimalMath $math) {}

    public function priceList(Customer $customer): PriceList
    {
        $list = PriceList::query()
            ->whereKey($customer->default_price_list_id)
            ->where('organization_id', $customer->organization_id)
            ->where('status', 'active')
            ->where(fn ($query) => $query
                ->whereNull('effective_from')
                ->orWhereDate('effective_from', '<=', today()))
            ->where(fn ($query) => $query
                ->whereNull('effective_to')
                ->orWhereDate('effective_to', '>=', today()))
            ->first();

        if ($list === null) {
            throw new InventoryDomainException(
                'sales_effective_price_list_missing',
                'The customer does not have an active effective price list.',
                409,
            );
        }

        return $list;
    }

    public function resolve(
        PriceList $priceList,
        ProductPackaging $packaging,
        string|int|float|null $requested,
    ): array {
        $item = PriceListItem::query()
            ->where('price_list_id', $priceList->id)
            ->where('product_packaging_id', $packaging->id)
            ->first();

        if ($item === null) {
            throw new InventoryDomainException(
                'sales_price_not_configured',
                'No selling price is configured for this packaging.',
                409,
            );
        }

        $list = (string) $item->selling_price;
        $floor = $this->floor($item, $list);
        $effective = $requested === null ? $list : (string) $requested;

        return [
            'list_unit_price' => $list,
            'floor_unit_price' => $floor,
            'effective_unit_price' => $effective,
            'price_exception' => $this->math->greaterThan($floor, $effective),
        ];
    }

    private function floor(PriceListItem $item, string $list): string
    {
        $floor = (string) ($item->minimum_selling_price ?? 0);

        if ($item->maximum_discount_percent !== null) {
            $discount = $this->math->percentOf(
                $list,
                (string) $item->maximum_discount_percent,
            );
            $discountFloor = $this->math->subtract($list, $discount);

            if ($this->math->greaterThan($discountFloor, $floor)) {
                $floor = $discountFloor;
            }
        }

        return $floor;
    }
}
