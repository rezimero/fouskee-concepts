<?php

namespace App\Services\Pricing;

use App\Enums\PriceListStatus;
use App\Models\Organization;
use App\Models\PriceList;
use App\Models\User;
use App\Services\Audit\AuditRecorder;

class PriceListCreator
{
    public function __construct(
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        array $data,
    ): PriceList {
        $priceList = PriceList::query()->create([
            'organization_id' => $organization->id,
            'code' => $data['code'],
            'name' => $data['name'],
            'currency_code' => strtoupper($data['currency_code'] ?? 'NGN'),
            'status' => PriceListStatus::Draft,
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
        ]);

        $this->audit->record(
            event: 'pricing.price_list.created',
            actor: $actor,
            subject: $priceList,
            organizationId: $organization->id,
            after: $priceList->only([
                'code',
                'name',
                'currency_code',
                'status',
                'effective_from',
                'effective_to',
            ]),
        );

        return $priceList;
    }
}
