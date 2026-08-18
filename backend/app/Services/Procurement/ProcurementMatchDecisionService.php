<?php

namespace App\Services\Procurement;

use App\Enums\ProcurementMatchResult;
use App\Enums\SupplierInvoiceStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\ProcurementMatchRun;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class ProcurementMatchDecisionService
{
    public function __construct(
        private readonly ApprovalLocator $approvalLocator,
        private readonly ApprovalService $approvals,
        private readonly AuditRecorder $audit,
    ) {}

    public function approve(
        Organization $organization,
        User $actor,
        string $runPublicId,
        string $reason,
    ): ProcurementMatchRun {
        return DB::transaction(function () use (
            $organization,
            $actor,
            $runPublicId,
            $reason,
        ) {
            $run = $this->locked($organization, $runPublicId);
            $run->setRelation(
                'invoice',
                SupplierInvoice::query()
                    ->whereKey($run->supplier_invoice_id)
                    ->lockForUpdate()
                    ->firstOrFail(),
            );
            $this->assertCurrentException($run);

            $approval = $this->approvalLocator->pending(
                $organization,
                'procurement.match',
                $run->public_id,
                'approve_exception',
            );
            $this->approvals->approve($approval, $actor, $reason);

            $run->invoice->update([
                'status' => SupplierInvoiceStatus::ExceptionApproved,
            ]);

            $this->audit->record(
                event: 'procurement.exception.approved',
                actor: $actor,
                subject: $run,
                organizationId: $organization->id,
                after: [
                    'supplier_invoice_id' => $run->invoice->public_id,
                    'status' => 'exception_approved',
                ],
                reason: $reason,
            );

            return $run->load($this->relations());
        }, 3);
    }

    public function reject(
        Organization $organization,
        User $actor,
        string $runPublicId,
        string $reason,
    ): ProcurementMatchRun {
        return DB::transaction(function () use (
            $organization,
            $actor,
            $runPublicId,
            $reason,
        ) {
            $run = $this->locked($organization, $runPublicId);
            $run->setRelation(
                'invoice',
                SupplierInvoice::query()
                    ->whereKey($run->supplier_invoice_id)
                    ->lockForUpdate()
                    ->firstOrFail(),
            );
            $this->assertCurrentException($run);

            $approval = $this->approvalLocator->pending(
                $organization,
                'procurement.match',
                $run->public_id,
                'approve_exception',
            );
            $this->approvals->reject($approval, $actor, $reason);

            $this->audit->record(
                event: 'procurement.exception.rejected',
                actor: $actor,
                subject: $run,
                organizationId: $organization->id,
                reason: $reason,
            );

            return $run->load($this->relations());
        }, 3);
    }

    private function locked(
        Organization $organization,
        string $publicId,
    ): ProcurementMatchRun {
        return ProcurementMatchRun::query()
            ->with($this->relations())
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertCurrentException(ProcurementMatchRun $run): void
    {
        if (
            $run->result !== ProcurementMatchResult::Exception
            || $run->invoice->latest_match_run_id !== $run->id
            || $run->invoice->status !== SupplierInvoiceStatus::Exception
        ) {
            throw new InventoryDomainException(
                'procurement_match_not_approvable',
                'Only the current exception match can be decided.',
                409,
            );
        }
    }

    private function relations(): array
    {
        return [
            'invoice.supplier',
            'lines.invoiceLine.product',
            'lines.invoiceLine.purchaseOrderLine',
        ];
    }
}
