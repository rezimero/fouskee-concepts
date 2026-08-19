<?php

namespace App\Services\Sales;

use App\Enums\ApprovalStatus;
use App\Models\ApprovalRequest;
use App\Models\Organization;

class SalesApprovalLocator
{
    public function pending(
        Organization $organization,
        string $publicId,
    ): ApprovalRequest {
        return ApprovalRequest::query()
            ->where('organization_id', $organization->id)
            ->where('subject_type', 'sales.order')
            ->where('subject_public_id', $publicId)
            ->where('action', 'approve_exception')
            ->where('status', ApprovalStatus::Pending)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
