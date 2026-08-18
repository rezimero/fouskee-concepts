<?php

namespace App\Services\Procurement;

use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Support\DecimalMath;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PurchaseOrderCreator
{
    public function __construct(
        private readonly PurchaseOrderLineFactory $lines,
        private readonly ProcurementReferenceNumber $references,
        private readonly ProcurementIdempotencyGuard $idempotency,
        private readonly ProcurementCurrencyPolicy $currencies,
        private readonly DecimalMath $math,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        string $key,
        array $data,
    ): PurchaseOrder {
        $fingerprint = $this->idempotency->fingerprint($data);

        try {
            return DB::transaction(function () use (
                $organization,
                $actor,
                $key,
                $data,
                $fingerprint,
            ) {
                $existing = $this->idempotency->existing(
                    PurchaseOrder::class,
                    $organization,
                    $key,
                    $fingerprint,
                );

                if ($existing !== null) {
                    return $existing->load($this->relations());
                }

                $supplier = Supplier::query()
                    ->where('organization_id', $organization->id)
                    ->where('public_id', $data['supplier_id'])
                    ->where('status', 'active')
                    ->firstOrFail();

                $lineData = [];
                $total = '0.000000';

                foreach ($data['lines'] as $index => $input) {
                    $line = $this->lines->make(
                        $organization,
                        $input,
                        $index + 1,
                    );
                    $lineData[] = $line;
                    $total = $this->math->add($total, $line['line_total']);
                }

                $order = PurchaseOrder::query()->create([
                    'organization_id' => $organization->id,
                    'purchase_order_number' => $this->references
                        ->purchaseOrder($organization),
                    'supplier_id' => $supplier->id,
                    'status' => 'draft',
                    'currency_code' => $this->currencies->resolve(
                        $organization,
                        $data['currency_code'] ?? null,
                    ),
                    'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'total_amount' => $total,
                    'idempotency_key' => $key,
                    'idempotency_fingerprint' => $fingerprint,
                    'created_by_user_id' => $actor->id,
                ]);

                foreach ($lineData as $line) {
                    $order->lines()->create($line);
                }

                $this->audit->record(
                    event: 'procurement.purchase_order.created',
                    actor: $actor,
                    subject: $order,
                    organizationId: $organization->id,
                    after: [
                        'purchase_order_number' => $order->purchase_order_number,
                        'supplier_id' => $supplier->public_id,
                        'total_amount' => $total,
                    ],
                );

                return $order->load($this->relations());
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }

            return $this->idempotency->existing(
                PurchaseOrder::class,
                $organization,
                $key,
                $fingerprint,
            )?->load($this->relations()) ?? throw $exception;
        }
    }

    private function relations(): array
    {
        return ['supplier', 'lines.product', 'lines.packaging', 'policyVersion'];
    }
}
