<?php

namespace App\Services\Procurement;

use App\Enums\ApprovalStatus;
use App\Models\ApprovalRequest;
use App\Models\Organization;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApprovalLocator
{
    public function pending(
        Organization $organization,
        string $subjectType,
        string $subjectPublicId,
        string $action,
    ): ApprovalRequest {
        return $this->findPending(
            $organization,
            $subjectType,
            $subjectPublicId,
            $action,
        ) ?? throw new ModelNotFoundException;
    }

    public function findPending(
        Organization $organization,
        string $subjectType,
        string $subjectPublicId,
        string $action,
    ): ?ApprovalRequest {
        return ApprovalRequest::query()
            ->where('organization_id', $organization->id)
            ->where('subject_type', $subjectType)
            ->where('subject_public_id', $subjectPublicId)
            ->where('action', $action)
            ->where('status', ApprovalStatus::Pending)
            ->latest('id')
            ->first();
    }
}
