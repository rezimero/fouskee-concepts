<?php

namespace App\Services\Procurement;

use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Support\DecimalMath;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SupplierInvoiceCreator
{
    public function __construct(
        private readonly ProcurementIdempotencyGuard $idempotency,
        private readonly SupplierInvoiceNumberNormalizer $numbers,
        private readonly SupplierInvoiceLineFactory $lineFactory,
        private readonly ProcurementCurrencyPolicy $currencies,
        private readonly DecimalMath $math,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        string $key,
        array $data,
    ): SupplierInvoice {
        $fingerprint = $this->idempotency->fingerprint($data);
        $normalized = $this->numbers->normalize($data['invoice_number']);

        try {
            return DB::transaction(function () use (
                $organization, $actor, $key, $data, $fingerprint, $normalized,
            ) {
                $existing = $this->idempotency->existing(
                    SupplierInvoice::class,
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

                $lines = [];
                $subtotal = '0.000000';

                foreach ($data['lines'] as $index => $input) {
                    $line = $this->lineFactory->make(
                        $organization,
                        $supplier,
                        $input,
                        $index + 1,
                    );
                    $lines[] = $line;
                    $subtotal = $this->math->add(
                        $subtotal,
                        $line['line_amount'],
                    );
                }

                $tax = (string) ($data['tax_amount'] ?? 0);
                $invoice = SupplierInvoice::query()->create([
                    'organization_id' => $organization->id,
                    'supplier_id' => $supplier->id,
                    'invoice_number' => trim($data['invoice_number']),
                    'normalized_invoice_number' => $normalized,
                    'invoice_date' => $data['invoice_date'],
                    'currency_code' => $this->currencies->resolve(
                        $organization,
                        $data['currency_code'] ?? null,
                    ),
                    'status' => 'recorded',
                    'subtotal_amount' => $subtotal,
                    'tax_amount' => $tax,
                    'total_amount' => $this->math->add($subtotal, $tax),
                    'idempotency_key' => $key,
                    'idempotency_fingerprint' => $fingerprint,
                    'recorded_by_user_id' => $actor->id,
                ]);

                foreach ($lines as $line) {
                    $invoice->lines()->create($line);
                }

                $this->audit->record(
                    event: 'procurement.supplier_invoice.recorded',
                    actor: $actor,
                    subject: $invoice,
                    organizationId: $organization->id,
                    after: [
                        'supplier_id' => $supplier->public_id,
                        'invoice_number' => $invoice->invoice_number,
                        'total_amount' => $invoice->total_amount,
                    ],
                );

                return $invoice->load($this->relations());
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }

            $retry = $this->idempotency->existing(
                SupplierInvoice::class,
                $organization,
                $key,
                $fingerprint,
            );

            if ($retry !== null) {
                return $retry->load($this->relations());
            }

            throw new InventoryDomainException(
                'procurement_duplicate_supplier_invoice',
                'This supplier invoice number has already been recorded.',
                409,
            );
        }
    }

    private function relations(): array
    {
        return [
            'supplier',
            'lines.product',
            'lines.purchaseOrderLine.purchaseOrder',
            'latestMatchRun.lines',
        ];
    }
}
