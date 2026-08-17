<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commercial\StoreCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\Commercial\CustomerCreator;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(
        Request $request,
        CurrentOrganization $currentOrganization,
    ) {
        $organization = $currentOrganization->for($request->user());
        $query = Customer::query()
            ->with('defaultPriceList')
            ->where('organization_id', $organization->id)
            ->orderBy('display_name');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($builder) => $builder
                ->where('display_name', 'like', "%{$search}%")
                ->orWhere('customer_number', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }

        return CustomerResource::collection(
            $query->paginate(min((int) $request->query('per_page', 25), 100)),
        );
    }

    public function store(
        StoreCustomerRequest $request,
        CurrentOrganization $currentOrganization,
        CustomerCreator $creator,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());
        $customer = $creator->create(
            $organization,
            $request->user(),
            $request->validated(),
        )->load('defaultPriceList');

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }
}
