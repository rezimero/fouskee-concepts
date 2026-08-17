<?php

namespace App\Services\Commercial;

use App\Enums\CommercialPartyStatus;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\PriceList;
use App\Models\User;
use App\Services\Audit\AuditRecorder;

class CustomerCreator
{
    public function __construct(
        private readonly ReferenceNumber $references,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        array $data,
    ): Customer {
        $priceList = $this->priceList($organization, $data['price_list_id'] ?? null);

        $customer = Customer::query()->create([
            'organization_id' => $organization->id,
            'customer_number' => $this->references->customer(),
            'segment' => $data['segment'],
            'display_name' => $data['display_name'],
            'legal_name' => $data['legal_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'tax_identifier' => $data['tax_identifier'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? 0,
            'default_price_list_id' => $priceList?->id,
            'status' => CommercialPartyStatus::Active,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->audit->record(
            event: 'customer.created',
            actor: $actor,
            subject: $customer,
            organizationId: $organization->id,
            after: $customer->only([
                'customer_number',
                'segment',
                'display_name',
                'credit_limit',
                'status',
            ]),
        );

        return $customer;
    }

    private function priceList(
        Organization $organization,
        ?string $publicId,
    ): ?PriceList {
        if ($publicId === null) {
            return null;
        }

        return PriceList::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->firstOrFail();
    }
}
