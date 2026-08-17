<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductCategoryRequest;
use App\Services\Catalog\ProductCategoryCreator;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    public function store(
        StoreProductCategoryRequest $request,
        CurrentOrganization $currentOrganization,
        ProductCategoryCreator $creator,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());
        $category = $creator->create(
            $organization,
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'data' => [
                'id' => $category->public_id,
                'code' => $category->code,
                'name' => $category->name,
            ],
        ], 201);
    }
}
