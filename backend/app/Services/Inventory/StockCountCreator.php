<?php

namespace App\Services\Inventory;

use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\Product;
use App\Models\StockCountSession;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class StockCountCreator
{
    public function __construct(
        private readonly InventoryLocationResolver $locations,
        private readonly InventoryLotService $lots,
        private readonly InventoryBalanceService $balances,
        private readonly InventoryQuantityMath $math,
        private readonly StockCountReferenceNumber $references,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(
        Organization $organization,
        User $actor,
        array $data,
    ): StockCountSession {
        return DB::transaction(function () use ($organization, $actor, $data) {
            $location = $this->locations->active($organization, $data['location_id']);

            $session = StockCountSession::query()->create([
                'organization_id' => $organization->id,
                'count_number' => $this->references->next($organization),
                'inventory_location_id' => $location->id,
                'status' => 'draft',
                'reason_code' => $data['reason_code'],
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);

            $seen = [];
            $total = '0.000000';

            foreach ($data['lines'] as $input) {
                [$line, $key] = $this->snapshotLine(
                    $organization,
                    $location->id,
                    $input,
                );

                if (isset($seen[$key])) {
                    throw new InventoryDomainException(
                        'inventory_count_duplicate_line',
                        'A product and lot may appear only once in a stock count.',
                        422,
                    );
                }

                $seen[$key] = true;
                $session->lines()->create($line);
                $total = $this->math->add(
                    $total,
                    $this->math->absolute($line['variance_value']),
                );
            }

            $session->update(['total_abs_variance_value' => $total]);

            $this->audit->record(
                event: 'inventory.count.created',
                actor: $actor,
                subject: $session,
                organizationId: $organization->id,
                after: [
                    'count_number' => $session->count_number,
                    'line_count' => count($data['lines']),
                    'total_abs_variance_value' => $total,
                ],
            );

            return $session->load(['lines.product', 'lines.lot', 'location']);
        }, 3);
    }

    private function snapshotLine(
        Organization $organization,
        int $locationId,
        array $input,
    ): array {
        $product = Product::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $input['product_id'])
            ->where('status', 'active')
            ->firstOrFail();

        $lot = $this->lots->find(
            $organization,
            $product,
            $input['lot_id'] ?? null,
        );

        $balance = $this->balances->lock(
            $organization->id,
            $locationId,
            $product->id,
            $lot?->id,
        );

        $counted = (string) $input['counted_quantity_base'];
        $variance = $this->math->add(
            $counted,
            $this->math->negate($balance->quantity_on_hand_base),
        );
        $cost = $this->cost($balance->weighted_average_unit_cost, $variance, $input);

        return [[
            'product_id' => $product->id,
            'inventory_lot_id' => $lot?->id,
            'system_quantity_base' => $balance->quantity_on_hand_base,
            'counted_quantity_base' => $counted,
            'variance_quantity_base' => $variance,
            'unit_cost_snapshot' => $cost,
            'variance_value' => $this->math->multiply($variance, $cost),
            'balance_version_snapshot' => $balance->version,
        ], $product->id.'-'.($lot?->id ?? 'none')];
    }

    private function cost(string $current, string $variance, array $input): string
    {
        if (! $this->math->isPositive($variance) || ! $this->math->isZero($current)) {
            return $current;
        }

        if (! isset($input['unit_cost_base'])) {
            throw new InventoryDomainException(
                'inventory_unit_cost_required',
                'A unit cost is required for newly discovered stock.',
                422,
            );
        }

        return (string) $input['unit_cost_base'];
    }
}
