<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CreateStockTransferRequest;
use App\Http\Resources\StockTransferResource;
use App\Models\StockTransfer;
use App\Services\Inventory\StockTransferCreator;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function index(Request $request, CurrentOrganization $currentOrganization)
    {
        $organization = $currentOrganization->for($request->user());

        return StockTransferResource::collection(
            StockTransfer::query()
                ->with(['sourceLocation', 'destinationLocation', 'transitLocation'])
                ->where('organization_id', $organization->id)
                ->orderByDesc('id')
                ->paginate(max(1, min((int) $request->query('per_page', 25), 100))),
        );
    }

    public function store(
        CreateStockTransferRequest $request,
        CurrentOrganization $currentOrganization,
        StockTransferCreator $creator,
    ): StockTransferResource {
        $organization = $currentOrganization->for($request->user());

        return new StockTransferResource($creator->create(
            $organization,
            $request->user(),
            $request->validated(),
        ));
    }

    public function show(
        Request $request,
        string $transferPublicId,
        CurrentOrganization $currentOrganization,
    ): StockTransferResource {
        $organization = $currentOrganization->for($request->user());

        return new StockTransferResource(
            StockTransfer::query()
                ->with([
                    'lines.product', 'lines.packaging', 'lines.lot',
                    'sourceLocation', 'destinationLocation', 'transitLocation',
                ])
                ->where('organization_id', $organization->id)
                ->where('public_id', $transferPublicId)
                ->firstOrFail(),
        );
    }
}
