<?php

namespace App\Services\Sales;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Support\DecimalMath;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SalesOrderCreator
{
    public function __construct(
        private readonly SalesOrderLineFactory $lines,
        private readonly SalesPriceResolver $prices,
        private readonly SalesReferenceNumber $references,
        private readonly SalesIdempotencyGuard $idempotency,
        private readonly DecimalMath $math,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        string $key,
        array $data,
    ): SalesOrder {
        $fingerprint = $this->idempotency->fingerprint($data);

        try {
            return DB::transaction(function () use (
                $organization, $actor, $key, $data, $fingerprint,
            ) {
                $existing = $this->idempotency->existing(
                    $organization,
                    $key,
                    $fingerprint,
                );
                if ($existing !== null) {
                    return $existing->load($this->relations());
                }

                $customer = Customer::query()
                    ->where('organization_id', $organization->id)
                    ->where('public_id', $data['customer_id'])
                    ->where('status', 'active')
                    ->firstOrFail();
                $priceList = $this->prices->priceList($customer);

                $lineData = [];
                $subtotal = '0.000000';

                foreach ($data['lines'] as $index => $input) {
                    $line = $this->lines->make(
                        $organization,
                        $priceList,
                        $input,
                        $index + 1,
                    );
                    $lineData[] = $line;
                    $subtotal = $this->math->add($subtotal, $line['line_total']);
                }

                $order = SalesOrder::query()->create([
                    'organization_id' => $organization->id,
                    'sales_order_number' => $this->references->order($organization),
                    'customer_id' => $customer->id,
                    'price_list_id' => $priceList->id,
                    'status' => 'draft',
                    'settlement_type' => $data['settlement_type'],
                    'currency_code' => $priceList->currency_code,
                    'subtotal_amount' => $subtotal,
                    'total_amount' => $subtotal,
                    'notes' => $data['notes'] ?? null,
                    'idempotency_key' => $key,
                    'idempotency_fingerprint' => $fingerprint,
                    'created_by_user_id' => $actor->id,
                ]);

                foreach ($lineData as $line) {
                    $order->lines()->create($line);
                }

                $this->audit->record(
                    event: 'sales.order.created',
                    actor: $actor,
                    subject: $order,
                    organizationId: $organization->id,
                    after: [
                        'sales_order_number' => $order->sales_order_number,
                        'customer_id' => $customer->public_id,
                        'total_amount' => $subtotal,
                    ],
                );

                return $order->load($this->relations());
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }

            return $this->idempotency->existing(
                $organization,
                $key,
                $fingerprint,
            )?->load($this->relations()) ?? throw $exception;
        }
    }

    private function relations(): array
    {
        return ['customer', 'priceList', 'lines.product', 'lines.packaging'];
    }
}
