<?php

namespace App\Services\Inventory;

use App\Enums\InventoryReservationStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\InventoryReservation;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Collection;

class InventoryOwnedReservationFulfillmentService
{
    public function __construct(
        private readonly InventoryReservationOwnershipPolicy $ownership,
        private readonly AuditRecorder $audit,
    ) {}

    public function consume(
        Organization $organization,
        User $actor,
        Collection $reservations,
    ): void {
        foreach ($reservations as $reservation) {
            $this->ownership->assertAllowed(
                $reservation->source_reference_type,
                true,
            );

            if ($reservation->status !== InventoryReservationStatus::Active) {
                throw new InventoryDomainException(
                    'inventory_reservation_not_active',
                    'Only active reservations can be fulfilled.',
                    409,
                );
            }

            $reservation->update([
                'status' => InventoryReservationStatus::Fulfilled,
                'fulfilled_by_user_id' => $actor->id,
                'fulfilled_at' => now(),
            ]);

            $this->audit->record(
                event: 'inventory.reservation.fulfilled',
                actor: $actor,
                subject: $reservation,
                organizationId: $organization->id,
                after: ['status' => InventoryReservationStatus::Fulfilled->value],
            );
        }
    }
}
