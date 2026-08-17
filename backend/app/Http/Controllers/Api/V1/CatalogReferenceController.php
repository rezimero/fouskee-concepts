<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogReferenceController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());

        $units = UnitOfMeasure::query()
            ->where(fn ($query) => $query
                ->whereNull('organization_id')
                ->orWhere('organization_id', $organization->id))
            ->where('status', 'active')
            ->orderBy('kind')
            ->orderBy('name')
            ->get(['public_id', 'code', 'name', 'symbol', 'kind']);

        return response()->json([
            'data' => [
                'units' => $units->map(fn ($unit) => [
                    'id' => $unit->public_id,
                    'code' => $unit->code,
                    'name' => $unit->name,
                    'symbol' => $unit->symbol,
                    'kind' => $unit->kind->value,
                ]),
                'brands' => $this->brands($organization->id),
                'categories' => $this->categories($organization->id),
            ],
        ]);
    }

    private function brands(int $organizationId): array
    {
        return Brand::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['public_id', 'code', 'name'])
            ->map(fn ($item) => [
                'id' => $item->public_id,
                'code' => $item->code,
                'name' => $item->name,
            ])
            ->all();
    }

    private function categories(int $organizationId): array
    {
        return ProductCategory::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['public_id', 'code', 'name'])
            ->map(fn ($item) => [
                'id' => $item->public_id,
                'code' => $item->code,
                'name' => $item->name,
            ])
            ->all();
    }
}
