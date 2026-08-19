<?php

namespace App\Services\Sales;

use App\Enums\SalesQuotationStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\SalesQuotation;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class SalesQuotationLifecycle
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function send(
        Organization $organization,
        User $actor,
        string $publicId,
    ): SalesQuotation {
        return $this->transition(
            $organization,
            $actor,
            $publicId,
            SalesQuotationStatus::Draft,
            SalesQuotationStatus::Sent,
            'sent_by_user_id',
            'sent_at',
            'sales.quotation.sent',
        );
    }

    public function accept(
        Organization $organization,
        User $actor,
        string $publicId,
    ): SalesQuotation {
        $quotation = $this->locked($organization, $publicId);

        if ($quotation->valid_until->isBefore(today())) {
            throw new InventoryDomainException(
                'sales_quotation_expired',
                'An expired quotation cannot be accepted.',
                409,
            );
        }

        return $this->transitionLocked(
            $quotation,
            $actor,
            SalesQuotationStatus::Sent,
            SalesQuotationStatus::Accepted,
            'accepted_by_user_id',
            'accepted_at',
            'sales.quotation.accepted',
        );
    }

    private function transition(
        Organization $organization,
        User $actor,
        string $publicId,
        SalesQuotationStatus $from,
        SalesQuotationStatus $to,
        string $actorColumn,
        string $timeColumn,
        string $event,
    ): SalesQuotation {
        return DB::transaction(function () use (
            $organization, $actor, $publicId, $from, $to,
            $actorColumn, $timeColumn, $event,
        ) {
            return $this->transitionLocked(
                $this->locked($organization, $publicId),
                $actor,
                $from,
                $to,
                $actorColumn,
                $timeColumn,
                $event,
            );
        }, 3);
    }

    private function transitionLocked(
        SalesQuotation $quotation,
        User $actor,
        SalesQuotationStatus $from,
        SalesQuotationStatus $to,
        string $actorColumn,
        string $timeColumn,
        string $event,
    ): SalesQuotation {
        if ($quotation->status === $to) {
            return $quotation->load($this->relations());
        }

        if ($quotation->status !== $from) {
            throw new InventoryDomainException(
                'sales_quotation_invalid_transition',
                'The quotation cannot make the requested transition.',
                409,
            );
        }

        $quotation->update([
            'status' => $to,
            $actorColumn => $actor->id,
            $timeColumn => now(),
        ]);

        $this->audit->record(
            event: $event,
            actor: $actor,
            subject: $quotation,
            organizationId: $quotation->organization_id,
            after: ['status' => $to->value],
        );

        return $quotation->load($this->relations());
    }

    private function locked(
        Organization $organization,
        string $publicId,
    ): SalesQuotation {
        return SalesQuotation::query()
            ->with($this->relations())
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function relations(): array
    {
        return ['customer', 'priceList', 'lines'];
    }
}
