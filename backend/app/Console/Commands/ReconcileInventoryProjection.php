<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Inventory\InventoryProjectionRebuilder;
use Illuminate\Console\Command;

class ReconcileInventoryProjection extends Command
{
    protected $signature = 'inventory:reconcile-projections
        {--organization= : Organization code}
        {--rebuild : Replace the derived projection from the immutable ledger}';

    protected $description = 'Compare or rebuild inventory balance projections';

    public function handle(InventoryProjectionRebuilder $rebuilder): int
    {
        $organizations = Organization::query()
            ->when(
                $this->option('organization'),
                fn ($query, $code) => $query->where('code', $code),
            )
            ->get();

        if ($organizations->isEmpty()) {
            $this->error('No matching organization was found.');

            return self::FAILURE;
        }

        $hasDifferences = false;

        foreach ($organizations as $organization) {
            $differences = $rebuilder->differences($organization);

            if ($differences === []) {
                $this->info("{$organization->code}: projection is reconciled.");

                continue;
            }

            $hasDifferences = true;
            $this->warn(
                "{$organization->code}: ".count($differences).' difference(s) found.',
            );

            if ($this->option('rebuild')) {
                $rebuilder->rebuild($organization);
                $this->info("{$organization->code}: projection rebuilt.");
                $hasDifferences = false;
            }
        }

        return $hasDifferences ? self::FAILURE : self::SUCCESS;
    }
}
