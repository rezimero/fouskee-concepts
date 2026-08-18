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

class StockCountSubmitter
{
    public function __construct(
        private readonly InventoryPolicyService $policies,
        private readonly StockCountIntegrityService $integrity,
        private readonly StockCountPoster $poster,
        private readonly InventoryQuantityMath $math,
        private readonly ApprovalService $approvals,
        private readonly AuditRecorder $audit,
    ) {}

    public function submit(
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

            if ($session->status !== StockCountStatus::Draft) {
                if ($session->submission_idempotency_key === $idempotencyKey) {
                    return $session;
                }

                throw new InventoryDomainException(
                    'inventory_count_not_draft',
                    'Only a draft stock count can be submitted.',
                    409,
                );
            }

            $this->integrity->assertFresh($session);

            $session->update([
                'submission_idempotency_key' => $idempotencyKey,
                'submitted_by_user_id' => $actor->id,
                'submitted_at' => now(),
            ]);

            $threshold = $this->policies->for($organization)
                ->count_variance_approval_value_threshold;

            if ($this->math->greaterThan(
                $session->total_abs_variance_value,
                $threshold,
            )) {
                $approval = $this->approvals->request(
                    $organization,
                    $actor,
                    'inventory.stock_count',
                    $session->public_id,
                    'post_variance',
                    [
                        'variance_value' => $session->total_abs_variance_value,
                        'threshold' => $threshold,
                    ],
                );

                $session->update([
                    'status' => StockCountStatus::PendingApproval,
                    'approval_request_id' => $approval->id,
                ]);

                $this->record($session, $actor, 'inventory.count.submitted', $reason);

                return $session->load(['lines.product', 'lines.lot', 'location']);
            }

            return $this->post($organization, $actor, $session, $idempotencyKey, $reason);
        }, 3);
    }

    private function post(
        Organization $organization,
        User $actor,
        StockCountSession $session,
        string $key,
        string $reason,
    ): StockCountSession {
        $movement = $this->poster->post($organization, $actor, $session, $key, $reason);

        $session->update([
            'status' => StockCountStatus::Posted,
            'posted_by_user_id' => $actor->id,
            'posted_movement_id' => $movement?->id,
            'posted_at' => now(),
        ]);

        $this->record($session, $actor, 'inventory.count.posted', $reason);

        return $session->load(['lines.product', 'lines.lot', 'location']);
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
