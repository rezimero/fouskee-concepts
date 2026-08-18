<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CreateStockCountRequest;
use App\Http\Resources\StockCountResource;
use App\Models\StockCountSession;
use App\Services\Inventory\StockCountCreator;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\Request;

class StockCountController extends Controller
{
    public function index(Request $request, CurrentOrganization $currentOrganization)
    {
        $organization = $currentOrganization->for($request->user());

        return StockCountResource::collection(
            StockCountSession::query()
                ->with(['location', 'approvalRequest', 'postedMovement'])
                ->where('organization_id', $organization->id)
                ->orderByDesc('id')
                ->paginate(max(1, min((int) $request->query('per_page', 25), 100))),
        );
    }

    public function store(
        CreateStockCountRequest $request,
        CurrentOrganization $currentOrganization,
        StockCountCreator $creator,
    ): StockCountResource {
        $organization = $currentOrganization->for($request->user());

        return new StockCountResource($creator->create(
            $organization,
            $request->user(),
            $request->validated(),
        ));
    }

    public function show(
        Request $request,
        string $countPublicId,
        CurrentOrganization $currentOrganization,
    ): StockCountResource {
        $organization = $currentOrganization->for($request->user());

        return new StockCountResource(
            StockCountSession::query()
                ->with([
                    'lines.product', 'lines.lot', 'location',
                    'approvalRequest', 'postedMovement',
                ])
                ->where('organization_id', $organization->id)
                ->where('public_id', $countPublicId)
                ->firstOrFail(),
        );
    }
}
