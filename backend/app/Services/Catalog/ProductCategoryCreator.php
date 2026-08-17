<?php

namespace App\Services\Catalog;

use App\Models\Organization;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;

class ProductCategoryCreator
{
    public function __construct(
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        array $data,
    ): ProductCategory {
        $category = ProductCategory::query()->create([
            'organization_id' => $organization->id,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'status' => 'active',
        ]);

        $this->audit->record(
            event: 'catalog.category.created',
            actor: $actor,
            subject: $category,
            organizationId: $organization->id,
            after: $category->only(['code', 'name', 'status']),
        );

        return $category;
    }
}
