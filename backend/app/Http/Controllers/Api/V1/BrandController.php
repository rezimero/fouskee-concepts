<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreBrandRequest;
use App\Services\Catalog\BrandCreator;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function store(
        StoreBrandRequest $request,
        CurrentOrganization $currentOrganization,
        BrandCreator $creator,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());
        $brand = $creator->create(
            $organization,
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'data' => [
                'id' => $brand->public_id,
                'code' => $brand->code,
                'name' => $brand->name,
            ],
        ], 201);
    }
}
