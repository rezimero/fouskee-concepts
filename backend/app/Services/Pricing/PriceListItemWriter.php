<?php

namespace App\Services\Pricing;

use App\Models\Organization;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\ProductPackaging;
use App\Models\User;
use App\Services\Audit\AuditRecorder;

class PriceListItemWriter
{
    public function __construct(
        private readonly AuditRecorder $audit,
    ) {}

    public function set(
        Organization $organization,
        User $actor,
        string $priceListPublicId,
        string $packagingPublicId,
        array $data,
        string $reason,
    ): PriceListItem {
        $priceList = PriceList::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $priceListPublicId)
            ->firstOrFail();

        $packaging = ProductPackaging::query()
            ->where('public_id', $packagingPublicId)
            ->whereHas('product', fn ($query) => $query
                ->where('organization_id', $organization->id))
            ->firstOrFail();

        $item = PriceListItem::query()->firstOrNew([
            'price_list_id' => $priceList->id,
            'product_packaging_id' => $packaging->id,
        ]);

        $before = $item->exists ? $item->only([
            'selling_price',
            'minimum_selling_price',
            'maximum_discount_percent',
        ]) : [];

        $item->fill([
            'selling_price' => $data['selling_price'],
            'minimum_selling_price' => $data['minimum_selling_price'] ?? null,
            'maximum_discount_percent' => $data['maximum_discount_percent'] ?? null,
        ])->save();

        $this->audit->record(
            event: 'pricing.price_list_item.set',
            actor: $actor,
            subject: $priceList,
            organizationId: $organization->id,
            before: $before,
            after: $item->only([
                'selling_price',
                'minimum_selling_price',
                'maximum_discount_percent',
            ]),
            context: ['product_packaging_id' => $packaging->public_id],
            reason: $reason,
        );

        return $item->load('packaging.product');
    }
}
