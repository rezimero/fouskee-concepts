<?php

namespace App\Services\Sales;

use App\Enums\SalesOrderStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class SalesOrderSubmitter
{
    public function __construct(
        private readonly SalesCreditExposure $credit,
        private readonly ApprovalService $approvals,
        private readonly AuditRecorder $audit,
    ) {}

    public function submit(
        Organization $organization,
        User $actor,
        string $publicId,
    ): SalesOrder {
        return DB::transaction(function () use ($organization, $actor, $publicId) {
            $order = $this->locked($organization, $publicId);

            if (in_array($order->status, [
                SalesOrderStatus::Submitted,
                SalesOrderStatus::Approved,
            ], true)) {
                return $order;
            }

            if ($order->status !== SalesOrderStatus::Draft) {
                throw new InventoryDomainException(
                    'sales_order_not_draft',
                    'Only a draft sales order can be submitted.',
                    409,
                );
            }

            $customer = Customer::query()
                ->whereKey($order->customer_id)
                ->lockForUpdate()
                ->firstOrFail();
            $credit = $this->credit->snapshot($customer, $order);
            $exceptions = $this->exceptions($order, $credit['over_limit']);
            $requiresApproval = count($exceptions) > 0;

            $order->update([
                'status' => $requiresApproval
                    ? SalesOrderStatus::Submitted
                    : SalesOrderStatus::Approved,
                'credit_limit_snapshot' => $credit['limit'],
                'credit_exposure_before_snapshot' => $credit['before'],
                'exception_codes' => $exceptions ?: null,
                'submitted_by_user_id' => $actor->id,
                'submitted_at' => now(),
                'approved_by_user_id' => $requiresApproval ? null : $actor->id,
                'approved_at' => $requiresApproval ? null : now(),
            ]);

            if ($requiresApproval) {
                $this->approvals->request(
                    $organization,
                    $actor,
                    'sales.order',
                    $order->public_id,
                    'approve_exception',
                    ['exception_codes' => $exceptions],
                );
            }

            $this->audit->record(
                event: 'sales.order.submitted',
                actor: $actor,
                subject: $order,
                organizationId: $organization->id,
                after: [
                    'status' => $order->status->value,
                    'exception_codes' => $exceptions,
                ],
            );

            return $order->load($this->relations());
        }, 3);
    }

    private function exceptions(SalesOrder $order, bool $creditOver): array
    {
        $exceptions = [];

        if ($order->lines->contains('price_exception', true)) {
            $exceptions[] = 'price_below_floor';
        }
        if ($creditOver) {
            $exceptions[] = 'customer_credit_limit_exceeded';
        }

        return $exceptions;
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
