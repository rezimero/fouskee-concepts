<?php

namespace Tests\Support;

use App\Models\Supplier;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

abstract class ProcurementTestCase extends InventoryTestCase
{
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Supplier::query()->create([
            'organization_id' => $this->organization->id,
            'supplier_number' => 'SUP-TEST',
            'display_name' => 'Procurement Test Supplier',
            'legal_name' => 'Procurement Test Supplier Ltd',
            'status' => 'active',
        ]);
    }

    protected function purchaseOrder(
        string $key = 'purchase-order-key-0001',
        int|float $quantity = 1,
        int|float $unitPrice = 50000,
    ) {
        return $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/procurement/purchase-orders', [
                'supplier_id' => $this->supplier->public_id,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'packaging_id' => $this->packaging->public_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]],
            ]);
    }

    protected function approveOrder(string $publicId): void
    {
        $this->postJson(
            "/api/v1/procurement/purchase-orders/{$publicId}/submit",
        )->assertSuccessful();

        $checker = $this->superadmin(
            'po-checker-'.Str::lower(Str::random(8)).'@example.test',
        );
        Sanctum::actingAs($checker);

        $this->withHeader(
            'X-Privileged-Reason',
            'Independent purchase-order authorization',
        )->postJson(
            "/api/v1/procurement/purchase-orders/{$publicId}/approve",
        )->assertSuccessful();

        Sanctum::actingAs($this->user);
    }

    protected function approvedOrder(
        string $key = 'approved-po-key-0001',
        int|float $quantity = 1,
        int|float $unitPrice = 50000,
    ): string {
        $publicId = $this->purchaseOrder($key, $quantity, $unitPrice)
            ->assertSuccessful()
            ->json('data.id');

        $this->approveOrder($publicId);

        return $publicId;
    }
}
