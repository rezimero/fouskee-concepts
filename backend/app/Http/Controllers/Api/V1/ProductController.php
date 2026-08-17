<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\Catalog\ProductCreator;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(
        Request $request,
        CurrentOrganization $currentOrganization,
    ) {
        $organization = $currentOrganization->for($request->user());
        $query = Product::query()
            ->with(['brand', 'category', 'baseUnit', 'packagings.unit'])
            ->where('organization_id', $organization->id)
            ->orderBy('name');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }

        return ProductResource::collection(
            $query->paginate(min((int) $request->query('per_page', 25), 100)),
        );
    }

    public function store(
        StoreProductRequest $request,
        CurrentOrganization $currentOrganization,
        ProductCreator $creator,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());
        $product = $creator->create(
            $organization,
            $request->user(),
            $request->validated(),
        );

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }
}
