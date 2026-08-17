<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditRecorder;

class BrandCreator
{
    public function __construct(
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        array $data,
    ): Brand {
        $brand = Brand::query()->create([
            'organization_id' => $organization->id,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'status' => 'active',
        ]);

        $this->audit->record(
            event: 'catalog.brand.created',
            actor: $actor,
            subject: $brand,
            organizationId: $organization->id,
            after: $brand->only(['code', 'name', 'status']),
        );

        return $brand;
    }
}
