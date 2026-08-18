<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\InventoryDomainException;
use App\Models\InventoryMovement;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class InventoryPoster
{
    public function __construct(
        private readonly InventoryBalanceService $balances,
        private readonly InventoryQuantityMath $math,
        private readonly InventoryReferenceNumber $references,
        private readonly AuditRecorder $audit,
    ) {}

    public function post(
        Organization $organization,
        User $actor,
        InventoryMovementType $type,
        string $idempotencyKey,
        array $lines,
        ?string $reason = null,
        ?InventoryMovement $reversalOf = null,
    ): InventoryMovement {
        $fingerprint = $this->fingerprint($type, $lines, $reversalOf, $reason);

        try {
            return DB::transaction(function () use (
                $organization,
                $actor,
                $type,
                $idempotencyKey,
                $lines,
                $reason,
                $reversalOf,
                $fingerprint,
            ): InventoryMovement {
                $existing = InventoryMovement::query()
                    ->where('organization_id', $organization->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    if ($existing->idempotency_fingerprint !== $fingerprint) {
                        throw new InventoryDomainException(
                            'inventory_idempotency_conflict',
                            'The idempotency key was already used for another command.',
                            409,
                        );
                    }

                    return $existing->load('lines');
                }

                $locked = $this->lockBalances($organization->id, $lines);
                foreach ($this->aggregateDeltas($lines) as $key => $delta) {
                    $this->balances->assertAvailable($locked[$key], $delta);
                }

                $movement = InventoryMovement::query()->create([
                    'organization_id' => $organization->id,
                    'movement_number' => $this->references->next($organization),
                    'movement_type' => $type,
                    'occurred_at' => now(),
                    'posted_at' => now(),
                    'posted_by_user_id' => $actor->id,
                    'idempotency_key' => $idempotencyKey,
                    'idempotency_fingerprint' => $fingerprint,
                    'reversal_of_movement_id' => $reversalOf?->id,
                    'business_reason' => $reason,
                ]);

                foreach ($lines as $line) {
                    $this->persistLine($movement, $line);
                    $this->balances->apply(
                        $locked[$this->key($line)],
                        (string) $line['quantity_base'],
                    );
                }

                $this->audit->record(
                    event: 'inventory.movement.posted',
                    actor: $actor,
                    subject: $movement,
                    organizationId: $organization->id,
                    after: [
                        'movement_number' => $movement->movement_number,
                        'movement_type' => $type->value,
                        'line_count' => count($lines),
                    ],
                    reason: $reason,
                );

                return $movement->load('lines');
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }

            $existing = InventoryMovement::query()
                ->where('organization_id', $organization->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing === null) {
                throw $exception;
            }

            if ($existing->idempotency_fingerprint !== $fingerprint) {
                throw new InventoryDomainException(
                    'inventory_idempotency_conflict',
                    'The idempotency key was already used for another command.',
                    409,
                );
            }

            return $existing->load('lines');
        }
    }

    private function lockBalances(int $organizationId, array $lines): array
    {
        $ordered = collect($lines)
            ->sortBy(fn (array $line) => $this->key($line))
            ->values();

        $locked = [];
        foreach ($ordered as $line) {
            $locked[$this->key($line)] = $this->balances->lock(
                $organizationId,
                $line['inventory_location_id'],
                $line['product_id'],
            );
        }

        return $locked;
    }

    private function aggregateDeltas(array $lines): array
    {
        $deltas = [];

        foreach ($lines as $line) {
            $key = $this->key($line);
            $deltas[$key] = isset($deltas[$key])
                ? $this->math->add($deltas[$key], (string) $line['quantity_base'])
                : (string) $line['quantity_base'];
        }

        return $deltas;
    }

    private function persistLine(InventoryMovement $movement, array $line): void
    {
        $movement->lines()->create($line);
    }

    private function key(array $line): string
    {
        return $line['inventory_location_id'].'-'.$line['product_id'];
    }

    private function fingerprint(
        InventoryMovementType $type,
        array $lines,
        ?InventoryMovement $reversalOf,
        ?string $reason,
    ): string {
        return hash('sha256', json_encode([
            'type' => $type->value,
            'reversal_of' => $reversalOf?->public_id,
            'reason' => $reason,
            'lines' => array_values($lines),
        ], JSON_THROW_ON_ERROR));
    }
}
