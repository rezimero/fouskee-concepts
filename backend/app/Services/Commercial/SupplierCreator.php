<?php

namespace App\Services\Commercial;

use App\Enums\CommercialPartyStatus;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Audit\AuditRecorder;

class SupplierCreator
{
    public function __construct(
        private readonly ReferenceNumber $references,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        array $data,
    ): Supplier {
        $supplier = Supplier::query()->create([
            'organization_id' => $organization->id,
            'supplier_number' => $this->references->supplier(),
            'display_name' => $data['display_name'],
            'legal_name' => $data['legal_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'tax_identifier' => $data['tax_identifier'] ?? null,
            'status' => CommercialPartyStatus::Active,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->audit->record(
            event: 'supplier.created',
            actor: $actor,
            subject: $supplier,
            organizationId: $organization->id,
            after: $supplier->only([
                'supplier_number',
                'display_name',
                'status',
            ]),
        );

        return $supplier;
    }
}
