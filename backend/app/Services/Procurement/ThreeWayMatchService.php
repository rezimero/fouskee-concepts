<?php

namespace App\Services\Procurement;

use App\Enums\ProcurementMatchResult;
use App\Models\Organization;
use App\Models\ProcurementMatchRun;
use App\Models\ProcurementPolicyVersion;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use App\Services\Support\DecimalMath;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ThreeWayMatchService
{
    public function __construct(
        private readonly ProcurementIdempotencyGuard $idempotency,
        private readonly ProcurementPolicyService $policies,
        private readonly ThreeWayMatchLineEvaluator $evaluator,
        private readonly ThreeWayMatchResultResolver $results,
        private readonly ApprovalService $approvals,
        private readonly DecimalMath $math,
        private readonly ProcurementMatchAudit $audit,
    ) {}

    public function match(
        Organization $organization,
        User $actor,
        string $invoicePublicId,
        string $key,
    ): ProcurementMatchRun {
        $fingerprint = $this->idempotency->fingerprint([
            'supplier_invoice_id' => $invoicePublicId,
        ]);

        try {
            return DB::transaction(function () use (
                $organization,
                $actor,
                $invoicePublicId,
                $key,
                $fingerprint,
            ) {
                $existing = $this->idempotency->existing(
                    ProcurementMatchRun::class,
                    $organization,
                    $key,
                    $fingerprint,
                );

                if ($existing !== null) {
                    return $existing->load($this->relations());
                }

                $invoice = $this->lockInvoice($organization, $invoicePublicId);
                $policy = $this->policies->current($organization);
                [$evaluated, $expected, $actual] = $this->evaluate(
                    $invoice,
                    $policy,
                );
                $overall = $this->results->overall(
                    $evaluated,
                    $expected,
                    $actual,
                    $policy->invoice_amount_tolerance_percent,
                );

                $run = ProcurementMatchRun::query()->create([
                    'organization_id' => $organization->id,
                    'supplier_invoice_id' => $invoice->id,
                    'run_number' => ProcurementMatchRun::query()
                        ->where('supplier_invoice_id', $invoice->id)
                        ->max('run_number') + 1,
                    'result' => $overall,
                    'expected_amount' => $expected,
                    'invoiced_amount' => $actual,
                    'amount_variance' => $this->math->subtract($actual, $expected),
                    'policy_version_id' => $policy->id,
                    'idempotency_key' => $key,
                    'idempotency_fingerprint' => $fingerprint,
                    'matched_by_user_id' => $actor->id,
                    'matched_at' => now(),
                ]);

                foreach ($evaluated as [$line, $result]) {
                    $run->lines()->create([
                        'supplier_invoice_line_id' => $line->id,
                        ...$result,
                        'result' => $result['result'],
                    ]);
                }

                $invoice->update([
                    'latest_match_run_id' => $run->id,
                    'status' => $this->results->invoiceStatus($overall),
                ]);

                $this->requestExceptionIfNeeded(
                    $organization,
                    $actor,
                    $invoice,
                    $run,
                    $overall,
                );
                $this->audit->record($organization, $actor, $invoice, $run, $overall);

                return $run->load($this->relations());
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }

            return $this->idempotency->existing(
                ProcurementMatchRun::class,
                $organization,
                $key,
                $fingerprint,
            )?->load($this->relations()) ?? throw $exception;
        }
    }

    private function evaluate(
        SupplierInvoice $invoice,
        ProcurementPolicyVersion $policy,
    ): array {
        $evaluated = [];
        $expected = '0.000000';
        $actual = '0.000000';

        foreach ($invoice->lines as $line) {
            $result = $this->evaluator->evaluate(
                $line,
                $policy,
                $invoice->currency_code,
            );
            $evaluated[] = [$line, $result];
            $expected = $this->math->add($expected, $result['expected_amount']);
            $actual = $this->math->add($actual, $result['invoiced_amount']);
        }

        return [$evaluated, $expected, $actual];
    }

    private function requestExceptionIfNeeded(
        Organization $organization,
        User $actor,
        SupplierInvoice $invoice,
        ProcurementMatchRun $run,
        ProcurementMatchResult $result,
    ): void {
        if ($result !== ProcurementMatchResult::Exception) {
            return;
        }

        $this->approvals->request(
            $organization,
            $actor,
            'procurement.match',
            $run->public_id,
            'approve_exception',
            [
                'supplier_invoice_id' => $invoice->public_id,
                'amount_variance' => $run->amount_variance,
            ],
        );
    }

    private function lockInvoice(
        Organization $organization,
        string $publicId,
    ): SupplierInvoice {
        return SupplierInvoice::query()
            ->with([
                'supplier',
                'lines.product',
                'lines.purchaseOrderLine.purchaseOrder',
            ])
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
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
