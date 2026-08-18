<?php

namespace App\Services\Inventory;

use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class StockTransferCreator
{
    public function __construct(
        private readonly InventoryLocationResolver $locations,
        private readonly InventoryLineResolver $resolver,
        private readonly InventoryLotService $lots,
        private readonly StockTransferReferenceNumber $references,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        array $data,
    ): StockTransfer {
        return DB::transaction(function () use ($organization, $actor, $data) {
            if ($data['source_location_id'] === $data['destination_location_id']) {
                throw new InventoryDomainException(
                    'inventory_transfer_same_location',
                    'Source and destination locations must differ.',
                    422,
                );
            }

            $source = $this->locations->active(
                $organization,
                $data['source_location_id'],
            );
            $destination = $this->locations->active(
                $organization,
                $data['destination_location_id'],
            );

            $transfer = StockTransfer::query()->create([
                'organization_id' => $organization->id,
                'transfer_number' => $this->references->next($organization),
                'source_location_id' => $source->id,
                'destination_location_id' => $destination->id,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);

            foreach ($data['lines'] as $input) {
                $resolved = $this->resolver->resolve($organization, $input);
                $lot = $this->lots->find(
                    $organization,
                    $resolved['product'],
                    $input['lot_id'] ?? null,
                );

                $transfer->lines()->create([
                    'product_id' => $resolved['product']->id,
                    'product_packaging_id' => $resolved['packaging']?->id,
                    'inventory_lot_id' => $lot?->id,
                    'entered_quantity' => $resolved['entered_quantity'],
                    'conversion_to_base_snapshot' => $resolved['conversion_to_base_snapshot'],
                    'quantity_base' => $resolved['quantity_base'],
                ]);
            }

            $this->audit->record(
                event: 'inventory.transfer.created',
                actor: $actor,
                subject: $transfer,
                organizationId: $organization->id,
                after: [
                    'transfer_number' => $transfer->transfer_number,
                    'line_count' => count($data['lines']),
                ],
            );

            return $transfer->load($this->relations());
        }, 3);
    }

    public function relations(): array
    {
        return [
            'lines.product',
            'lines.packaging',
            'lines.lot',
            'sourceLocation',
            'destinationLocation',
        ];
    }
}
