<?php

namespace App\Services\Sales;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\SalesQuotation;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Support\DecimalMath;
use Illuminate\Support\Facades\DB;

class SalesQuotationCreator
{
    public function __construct(
        private readonly SalesOrderLineFactory $lineFactory,
        private readonly SalesPriceResolver $prices,
        private readonly SalesReferenceNumber $references,
        private readonly SalesQuotationIdempotencyGuard $idempotency,
        private readonly DecimalMath $math,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        string $key,
        array $data,
    ): SalesQuotation {
        $fingerprint = $this->idempotency->fingerprint($data);

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
            $lines = [];
            $subtotal = '0.000000';

            foreach ($data['lines'] as $index => $input) {
                $line = $this->lineFactory->make(
                    $organization,
                    $priceList,
                    $input,
                    $index + 1,
                );
                $lines[] = $this->quotationLine($line);
                $subtotal = $this->math->add($subtotal, $line['line_total']);
            }

            $quotation = SalesQuotation::query()->create([
                'organization_id' => $organization->id,
                'quotation_number' => $this->references->quotation($organization),
                'customer_id' => $customer->id,
                'price_list_id' => $priceList->id,
                'status' => 'draft',
                'settlement_type' => $data['settlement_type'],
                'currency_code' => $priceList->currency_code,
                'subtotal_amount' => $subtotal,
                'total_amount' => $subtotal,
                'valid_until' => $data['valid_until'],
                'notes' => $data['notes'] ?? null,
                'idempotency_key' => $key,
                'idempotency_fingerprint' => $fingerprint,
                'created_by_user_id' => $actor->id,
            ]);

            foreach ($lines as $line) {
                $quotation->lines()->create($line);
            }

            $this->audit->record(
                event: 'sales.quotation.created',
                actor: $actor,
                subject: $quotation,
                organizationId: $organization->id,
                after: [
                    'quotation_number' => $quotation->quotation_number,
                    'total_amount' => $subtotal,
                ],
            );

            return $quotation->load($this->relations());
        }, 3);
    }

    private function quotationLine(array $line): array
    {
        $line['quoted_quantity'] = $line['ordered_quantity'];
        $line['quoted_quantity_base'] = $line['ordered_quantity_base'];
        unset($line['ordered_quantity'], $line['ordered_quantity_base']);

        return $line;
    }

    private function relations(): array
    {
        return ['customer', 'priceList', 'lines'];
    }
}
