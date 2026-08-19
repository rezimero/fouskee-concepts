<?php

namespace App\Services\Sales;

use App\Enums\SalesOrderStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class SalesOrderDecisionService
{
    public function __construct(
        private readonly SalesApprovalLocator $locator,
        private readonly ApprovalService $approvals,
        private readonly AuditRecorder $audit,
    ) {}

    public function approve(
        Organization $organization,
        User $actor,
        string $publicId,
        string $reason,
    ): SalesOrder {
        return $this->decide(
            $organization,
            $actor,
            $publicId,
            $reason,
            true,
        );
    }

    public function reject(
        Organization $organization,
        User $actor,
        string $publicId,
        string $reason,
    ): SalesOrder {
        return $this->decide(
            $organization,
            $actor,
            $publicId,
            $reason,
            false,
        );
    }

    private function decide(
        Organization $organization,
        User $actor,
        string $publicId,
        string $reason,
        bool $approve,
    ): SalesOrder {
        return DB::transaction(function () use (
            $organization, $actor, $publicId, $reason, $approve,
        ) {
            $order = $this->locked($organization, $publicId);

            if ($order->status !== SalesOrderStatus::Submitted) {
                throw new InventoryDomainException(
                    'sales_order_not_awaiting_approval',
                    'The sales order is not awaiting approval.',
                    409,
                );
            }

            $approval = $this->locator->pending($organization, $publicId);

            if ($approve) {
                $this->approvals->approve($approval, $actor, $reason);
                $order->update([
                    'status' => SalesOrderStatus::Approved,
                    'approved_by_user_id' => $actor->id,
                    'approved_at' => now(),
                ]);
            } else {
                $this->approvals->reject($approval, $actor, $reason);
                $order->update([
                    'status' => SalesOrderStatus::Cancelled,
                    'cancelled_by_user_id' => $actor->id,
                    'cancelled_at' => now(),
                ]);
            }

            $this->audit->record(
                event: $approve ? 'sales.order.approved' : 'sales.order.rejected',
                actor: $actor,
                subject: $order,
                organizationId: $organization->id,
                after: ['status' => $order->status->value],
                reason: $reason,
            );

            return $order->load($this->relations());
        }, 3);
    }

    private function locked(
        Organization $organization,
        string $publicId,
    ): SalesOrder {
        return SalesOrder::query()
            ->with($this->relations())
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function relations(): array
    {
        return ['customer', 'priceList', 'lines.product', 'lines.packaging'];
    }
}
