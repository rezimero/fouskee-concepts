<?php

namespace App\Services\Inventory;

use App\Enums\StockCountStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\StockCountSession;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class StockCountDecisionService
{
    public function __construct(
        private readonly StockCountIntegrityService $integrity,
        private readonly StockCountPoster $poster,
        private readonly ApprovalService $approvals,
        private readonly AuditRecorder $audit,
    ) {}

    public function approve(
        Organization $organization,
        User $actor,
        string $publicId,
        string $idempotencyKey,
        string $reason,
    ): StockCountSession {
        return DB::transaction(function () use (
            $organization,
            $actor,
            $publicId,
            $idempotencyKey,
            $reason,
        ) {
            $session = $this->locked($organization, $publicId);

            if (
                $session->status === StockCountStatus::Posted
                && $session->approval_idempotency_key === $idempotencyKey
            ) {
                return $session;
            }

            $this->assertPending($session);
            $this->integrity->assertFresh($session);

            $this->approvals->approve($session->approvalRequest, $actor, $reason);
            $movement = $this->poster->post(
                $organization,
                $actor,
                $session,
                $idempotencyKey,
                $reason,
            );

            $session->update([
                'status' => StockCountStatus::Posted,
                'approval_idempotency_key' => $idempotencyKey,
                'approved_by_user_id' => $actor->id,
                'posted_by_user_id' => $actor->id,
                'posted_movement_id' => $movement?->id,
                'approved_at' => now(),
                'posted_at' => now(),
            ]);

            $this->record($session, $actor, 'inventory.adjustment.approved', $reason);

            return $session->load(['lines.product', 'lines.lot', 'location']);
        }, 3);
    }

    public function reject(
        Organization $organization,
        User $actor,
        string $publicId,
        string $reason,
    ): StockCountSession {
        return DB::transaction(function () use (
            $organization,
            $actor,
            $publicId,
            $reason,
        ) {
            $session = $this->locked($organization, $publicId);
            $this->assertPending($session);
            $this->approvals->reject($session->approvalRequest, $actor, $reason);

            $session->update([
                'status' => StockCountStatus::Rejected,
            ]);

            $this->record($session, $actor, 'inventory.count.rejected', $reason);

            return $session->load(['lines.product', 'lines.lot', 'location']);
        }, 3);
    }

    private function locked(
        Organization $organization,
        string $publicId,
    ): StockCountSession {
        return StockCountSession::query()
            ->with(['lines.product', 'lines.lot', 'location', 'approvalRequest'])
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertPending(StockCountSession $session): void
    {
        if ($session->status !== StockCountStatus::PendingApproval) {
            throw new InventoryDomainException(
                'inventory_count_not_pending_approval',
                'The stock count is not awaiting approval.',
                409,
            );
        }
    }

    private function record(
        StockCountSession $session,
        User $actor,
        string $event,
        string $reason,
    ): void {
        $this->audit->record(
            event: $event,
            actor: $actor,
            subject: $session,
            organizationId: $session->organization_id,
            after: ['status' => $session->status->value],
            reason: $reason,
        );
    }
}
