<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockTransferActionRequest;
use App\Http\Resources\StockTransferResource;
use App\Services\Inventory\StockTransferCancellationService;
use App\Services\Inventory\StockTransferWorkflow;
use App\Services\Organization\CurrentOrganization;

class StockTransferActionController extends Controller
{
    public function dispatch(
        StockTransferActionRequest $request,
        string $transferPublicId,
        CurrentOrganization $currentOrganization,
        StockTransferWorkflow $workflow,
    ): StockTransferResource {
        $organization = $currentOrganization->for($request->user());
        $data = $request->validated();

        return new StockTransferResource($workflow->dispatch(
            $organization, $request->user(), $transferPublicId,
            $data['idempotency_key'], $data['occurred_at'] ?? null,
            $request->header('X-Privileged-Reason'),
        ));
    }

    public function receive(
        StockTransferActionRequest $request,
        string $transferPublicId,
        CurrentOrganization $currentOrganization,
        StockTransferWorkflow $workflow,
    ): StockTransferResource {
        $organization = $currentOrganization->for($request->user());
        $data = $request->validated();

        return new StockTransferResource($workflow->receive(
            $organization, $request->user(), $transferPublicId,
            $data['idempotency_key'], $data['occurred_at'] ?? null,
            $request->header('X-Privileged-Reason'),
        ));
    }

    public function cancel(
        StockTransferActionRequest $request,
        string $transferPublicId,
        CurrentOrganization $currentOrganization,
        StockTransferCancellationService $service,
    ): StockTransferResource {
        $organization = $currentOrganization->for($request->user());

        return new StockTransferResource($service->cancel(
            $organization,
            $request->user(),
            $transferPublicId,
            $request->validated('idempotency_key'),
            trim((string) $request->header('X-Privileged-Reason')),
        ));
    }
}
