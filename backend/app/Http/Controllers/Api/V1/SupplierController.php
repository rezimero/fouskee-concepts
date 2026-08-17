<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commercial\StoreSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\Commercial\SupplierCreator;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(
        Request $request,
        CurrentOrganization $currentOrganization,
    ) {
        $organization = $currentOrganization->for($request->user());
        $query = Supplier::query()
            ->where('organization_id', $organization->id)
            ->orderBy('display_name');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($builder) => $builder
                ->where('display_name', 'like', "%{$search}%")
                ->orWhere('supplier_number', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }

        return SupplierResource::collection(
            $query->paginate(min((int) $request->query('per_page', 25), 100)),
        );
    }

    public function store(
        StoreSupplierRequest $request,
        CurrentOrganization $currentOrganization,
        SupplierCreator $creator,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());
        $supplier = $creator->create(
            $organization,
            $request->user(),
            $request->validated(),
        );

        return (new SupplierResource($supplier))
            ->response()
            ->setStatusCode(201);
    }
}
