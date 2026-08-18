<?php

namespace App\Services\Procurement;

use App\Models\GoodsReceipt;
use App\Models\Organization;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use App\Services\Audit\AuditRecorder;

class GoodsReceiptExceptionRequester
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly AuditRecorder $audit,
    ) {}

    public function request(
        Organization $organization,
        User $actor,
        GoodsReceipt $receipt,
        string $tolerance,
    ): void {
        $this->approvals->request(
            $organization,
            $actor,
            'procurement.goods_receipt',
            $receipt->public_id,
            'over_receipt',
            ['over_receipt_tolerance_percent' => $tolerance],
        );

        $this->audit->record(
            event: 'procurement.goods_receipt.exception_requested',
            actor: $actor,
            subject: $receipt,
            organizationId: $organization->id,
            after: ['status' => 'pending_exception'],
        );
    }
}
