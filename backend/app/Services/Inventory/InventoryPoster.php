<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class InventoryPoster
{
    public function __construct(
        private readonly InventoryBalanceCoordinator $coordinator,
        private readonly InventoryBalanceProjector $projector,
        private readonly InventoryValuationService $valuation,
        private readonly InventoryIdempotencyGuard $idempotency,
        private readonly InventoryBalanceKey $keys,
        private readonly InventoryReferenceNumber $references,
        private readonly AuditRecorder $audit,
    ) {}

    public function post(
        Organization $organization,
        User $actor,
        InventoryMovementType $type,
        string $idempotencyKey,
        array $lines,
        ?InventoryPostingContext $context = null,
    ): InventoryMovement {
        $context ??= new InventoryPostingContext;
        $fingerprint = $this->fingerprint($type, $lines, $context);

        try {
            return DB::transaction(function () use (
                $organization,
                $actor,
                $type,
                $idempotencyKey,
                $lines,
                $context,
                $fingerprint,
            ): InventoryMovement {
                $existing = $this->idempotency->existing(
                    $organization,
                    $idempotencyKey,
                    $fingerprint,
                );

                if ($existing !== null) {
                    return $existing;
                }

                $locked = $this->coordinator->lockAll($organization->id, $lines);
                $this->coordinator->assertAvailability($lines, $locked);

                $valued = $this->valuation->value($type, $lines, $locked);
                $movement = $this->createMovement(
                    $organization,
                    $actor,
                    $type,
                    $idempotencyKey,
                    $fingerprint,
                    $context,
                );

                foreach ($valued as $line) {
                    $movement->lines()->create($line);
                }

                $quantityTotals = $this->coordinator->aggregate(
                    $valued,
                    'quantity_base',
                );
                $valueTotals = $this->coordinator->aggregate(
                    $valued,
                    'inventory_value_delta',
                );

                foreach ($quantityTotals as $key => $quantityDelta) {
                    $this->projector->apply(
                        $locked[$key],
                        $quantityDelta,
                        $valueTotals[$key],
                    );
                }

                $this->recordAudit($organization, $actor, $movement, $context);

                return $movement->load('lines');
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }

            return $this->idempotency->existing(
                $organization,
                $idempotencyKey,
                $fingerprint,
            ) ?? throw $exception;
        }
    }

    private function createMovement(
        Organization $organization,
        User $actor,
        InventoryMovementType $type,
        string $key,
        string $fingerprint,
        InventoryPostingContext $context,
    ): InventoryMovement {
        return InventoryMovement::query()->create([
            'organization_id' => $organization->id,
            'movement_number' => $this->references->next($organization),
            'movement_type' => $type,
            'occurred_at' => $context->occurredAt ?? now(),
            'posted_at' => now(),
            'posted_by_user_id' => $actor->id,
            'idempotency_key' => $key,
            'idempotency_fingerprint' => $fingerprint,
            'source_reference_type' => $context->sourceReferenceType,
            'source_reference_public_id' => $context->sourceReferencePublicId,
            'reversal_of_movement_id' => $context->reversalOf?->id,
            'business_reason' => $context->reason,
        ]);
    }

    private function recordAudit(
        Organization $organization,
        User $actor,
        InventoryMovement $movement,
        InventoryPostingContext $context,
    ): void {
        $this->audit->record(
            event: 'inventory.movement.posted',
            actor: $actor,
            subject: $movement,
            organizationId: $organization->id,
            after: [
                'movement_number' => $movement->movement_number,
                'movement_type' => $movement->movement_type->value,
                'occurred_at' => $movement->occurred_at?->toISOString(),
                'line_count' => $movement->lines()->count(),
            ],
            context: [
                'source_type' => $context->sourceReferenceType,
                'source_id' => $context->sourceReferencePublicId,
            ],
            reason: $context->reason,
        );
    }

    private function fingerprint(
        InventoryMovementType $type,
        array $lines,
        InventoryPostingContext $context,
    ): string {
        return hash('sha256', json_encode([
            'type' => $type->value,
            'reason' => $context->reason,
            'occurred_at' => $context->occurredAt?->toISOString(),
            'source_type' => $context->sourceReferenceType,
            'source_id' => $context->sourceReferencePublicId,
            'reversal_of' => $context->reversalOf?->public_id,
            'lines' => array_values($lines),
        ], JSON_THROW_ON_ERROR));
    }
}
