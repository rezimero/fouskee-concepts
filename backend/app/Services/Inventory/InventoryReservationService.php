<?php

namespace App\Services\Inventory;

use App\Enums\InventoryReservationStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\InventoryLocation;
use App\Models\InventoryReservation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class InventoryReservationService
{
    public function __construct(
        private readonly InventoryBalanceService $balances,
        private readonly InventoryQuantityMath $math,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        array $data,
    ): InventoryReservation {
        return DB::transaction(function () use ($organization, $actor, $data) {
            $location = InventoryLocation::query()
                ->where('organization_id', $organization->id)
                ->where('public_id', $data['location_id'])
                ->firstOrFail();

            $product = Product::query()
                ->where('organization_id', $organization->id)
                ->where('public_id', $data['product_id'])
                ->firstOrFail();

            $balance = $this->balances->lock(
                $organization->id,
                $location->id,
                $product->id,
            );

            $reserved = $this->balances->reservedBase(
                $organization->id,
                $location->id,
                $product->id,
            );

            $available = $this->math->add(
                $balance->quantity_on_hand_base,
                $this->math->negate($reserved),
            );
            $remaining = $this->math->add(
                $available,
                $this->math->negate((string) $data['quantity_base']),
            );

            if ($this->math->isNegative($remaining)) {
                throw new InventoryDomainException(
                    'inventory_insufficient_available_stock',
                    'The reservation exceeds available stock.',
                    409,
                );
            }

            $reservation = InventoryReservation::query()->create([
                'organization_id' => $organization->id,
                'inventory_location_id' => $location->id,
                'product_id' => $product->id,
                'quantity_base' => $data['quantity_base'],
                'source_reference_type' => $data['source_reference_type'] ?? null,
                'source_reference_public_id' => $data['source_reference_public_id'] ?? null,
                'status' => InventoryReservationStatus::Active,
                'expires_at' => $data['expires_at'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);

            $this->audit->record(
                event: 'inventory.reservation.created',
                actor: $actor,
                subject: $reservation,
                organizationId: $organization->id,
                after: ['quantity_base' => $reservation->quantity_base],
            );

            return $reservation;
        }, 3);
    }

    public function release(
        Organization $organization,
        User $actor,
        string $publicId,
    ): InventoryReservation {
        return DB::transaction(function () use ($organization, $actor, $publicId) {
            $reservation = InventoryReservation::query()
                ->where('organization_id', $organization->id)
                ->where('public_id', $publicId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reservation->status !== InventoryReservationStatus::Active) {
                throw new InventoryDomainException(
                    'inventory_reservation_not_active',
                    'Only an active reservation can be released.',
                    409,
                );
            }

            $reservation->update([
                'status' => InventoryReservationStatus::Released,
                'released_by_user_id' => $actor->id,
                'released_at' => now(),
            ]);

            $this->audit->record(
                event: 'inventory.reservation.released',
                actor: $actor,
                subject: $reservation,
                organizationId: $organization->id,
            );

            return $reservation;
        }, 3);
    }
}
