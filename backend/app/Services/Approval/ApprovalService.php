<?php

namespace App\Services\Approval;

use App\Enums\ApprovalStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\ApprovalRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditRecorder;

class ApprovalService
{
    public function __construct(
        private readonly AuditRecorder $audit,
    ) {}

    public function request(
        Organization $organization,
        User $actor,
        string $subjectType,
        string $subjectPublicId,
        string $action,
        array $context = [],
    ): ApprovalRequest {
        $approval = ApprovalRequest::query()->create([
            'organization_id' => $organization->id,
            'subject_type' => $subjectType,
            'subject_public_id' => $subjectPublicId,
            'action' => $action,
            'status' => ApprovalStatus::Pending,
            'requested_by_user_id' => $actor->id,
            'requested_at' => now(),
            'context' => $context ?: null,
        ]);

        $this->audit->record(
            event: 'approval.requested',
            actor: $actor,
            subject: $approval,
            organizationId: $organization->id,
            after: [
                'subject_type' => $subjectType,
                'subject_public_id' => $subjectPublicId,
                'action' => $action,
            ],
        );

        return $approval;
    }

    public function approve(
        ApprovalRequest $approval,
        User $actor,
        string $reason,
    ): ApprovalRequest {
        $this->assertPendingAndMakerChecker($approval, $actor);

        $approval->update([
            'status' => ApprovalStatus::Approved,
            'decided_by_user_id' => $actor->id,
            'decided_at' => now(),
            'decision_reason' => $reason,
        ]);

        $this->recordDecision(
            $approval,
            $actor,
            'approval.approved',
            $reason,
        );

        return $approval;
    }

    public function reject(
        ApprovalRequest $approval,
        User $actor,
        string $reason,
    ): ApprovalRequest {
        $this->assertPendingAndMakerChecker($approval, $actor);

        $approval->update([
            'status' => ApprovalStatus::Rejected,
            'decided_by_user_id' => $actor->id,
            'decided_at' => now(),
            'decision_reason' => $reason,
        ]);

        $this->recordDecision(
            $approval,
            $actor,
            'approval.rejected',
            $reason,
        );

        return $approval;
    }

    public function cancel(
        ApprovalRequest $approval,
        User $actor,
        string $reason,
    ): ApprovalRequest {
        if ($approval->status !== ApprovalStatus::Pending) {
            throw new InventoryDomainException(
                'approval_not_pending',
                'Only a pending approval request can be cancelled.',
                409,
            );
        }

        $approval->update([
            'status' => ApprovalStatus::Cancelled,
            'decided_by_user_id' => $actor->id,
            'decided_at' => now(),
            'decision_reason' => $reason,
        ]);

        $this->recordDecision(
            $approval,
            $actor,
            'approval.cancelled',
            $reason,
        );

        return $approval;
    }

    private function assertPendingAndMakerChecker(
        ApprovalRequest $approval,
        User $actor,
    ): void {
        if ($approval->status !== ApprovalStatus::Pending) {
            throw new InventoryDomainException(
                'approval_not_pending',
                'Only a pending approval request can be decided.',
                409,
            );
        }

        if ($approval->requested_by_user_id === $actor->id) {
            throw new InventoryDomainException(
                'approval_self_decision_forbidden',
                'The maker cannot approve or reject their own request.',
                409,
            );
        }
    }

    private function recordDecision(
        ApprovalRequest $approval,
        User $actor,
        string $event,
        string $reason,
    ): void {
        $this->audit->record(
            event: $event,
            actor: $actor,
            subject: $approval,
            organizationId: $approval->organization_id,
            after: ['status' => $approval->status->value],
            reason: $reason,
        );
    }
}
