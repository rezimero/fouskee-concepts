<?php

namespace App\Services\Procurement;

use App\Enums\ProcurementMatchResult;
use App\Models\Organization;
use App\Models\ProcurementMatchRun;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Services\Audit\AuditRecorder;

class ProcurementMatchAudit
{
    public function __construct(
        private readonly AuditRecorder $audit,
    ) {}

    public function record(
        Organization $organization,
        User $actor,
        SupplierInvoice $invoice,
        ProcurementMatchRun $run,
        ProcurementMatchResult $result,
    ): void {
        $this->audit->record(
            event: 'procurement.match.completed',
            actor: $actor,
            subject: $run,
            organizationId: $organization->id,
            after: [
                'result' => $result->value,
                'supplier_invoice_id' => $invoice->public_id,
                'amount_variance' => $run->amount_variance,
            ],
        );
    }
}
