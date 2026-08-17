<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\StorePriceListRequest;
use App\Http\Resources\PriceListResource;
use App\Models\PriceList;
use App\Services\Organization\CurrentOrganization;
use App\Services\Pricing\PriceListCreator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceListController extends Controller
{
    public function index(
        Request $request,
        CurrentOrganization $currentOrganization,
    ) {
        $organization = $currentOrganization->for($request->user());

        return PriceListResource::collection(
            PriceList::query()
                ->where('organization_id', $organization->id)
                ->orderBy('name')
                ->paginate(min((int) $request->query('per_page', 25), 100)),
        );
    }

    public function store(
        StorePriceListRequest $request,
        CurrentOrganization $currentOrganization,
        PriceListCreator $creator,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());
        $priceList = $creator->create(
            $organization,
            $request->user(),
            $request->validated(),
        );

        return (new PriceListResource($priceList))
            ->response()
            ->setStatusCode(201);
    }
}
