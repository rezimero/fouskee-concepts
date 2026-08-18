<?php

namespace Tests\Feature\Procurement;

use App\Models\ProcurementPolicyVersion;
use Tests\Support\ProcurementTestCase;

class ProcurementPolicyTest extends ProcurementTestCase
{
    public function test_policy_is_versioned_and_can_auto_approve_low_value_po(): void
    {
        $payload = [
            'purchase_order_approval_value_threshold' => 100000,
            'over_receipt_tolerance_percent' => 2,
            'invoice_quantity_tolerance_percent' => 1,
            'invoice_price_tolerance_percent' => 1,
            'invoice_amount_tolerance_percent' => 1,
        ];

        $this->putJson('/api/v1/procurement/policy', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('code', 'privileged_reason_required');

        $this->withHeader(
            'X-Privileged-Reason',
            'Setting procurement approval and matching tolerances',
        )->putJson('/api/v1/procurement/policy', $payload)
            ->assertCreated()
            ->assertJsonPath(
                'data.purchase_order_approval_value_threshold',
                '100000.000000',
            );

        $this->assertSame(1, ProcurementPolicyVersion::query()->count());

        $order = $this->purchaseOrder('policy-auto-po-key')
            ->assertSuccessful()
            ->json('data.id');

        $this->postJson(
            "/api/v1/procurement/purchase-orders/{$order}/submit",
        )->assertSuccessful()
            ->assertJsonPath('data.status', 'approved');
    }
}
