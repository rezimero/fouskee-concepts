<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\PickSalesFulfillmentRequest;
use App\Http\Requests\Sales\SalesFulfillmentActionRequest;
use App\Http\Resources\SalesFulfillmentResource;
use App\Models\SalesFulfillment;
use App\Services\Organization\CurrentOrganization;
use App\Services\Sales\SalesFulfillmentDispatcher;
use App\Services\Sales\SalesFulfillmentPicker;
use App\Services\Sales\SalesFulfillmentReversalService;
use Illuminate\Http\Request;

class SalesFulfillmentController extends Controller
{
    public function pick(
        PickSalesFulfillmentRequest $request,
        string $salesOrderPublicId,
        CurrentOrganization $current,
        SalesFulfillmentPicker $picker,
    ): SalesFulfillmentResource {
        $organization = $current->for($request->user());

        return new SalesFulfillmentResource($picker->pick(
            $organization,
            $request->user(),
            $salesOrderPublicId,
            $request->idempotencyKey(),
            $request->validated('reservation_ids'),
        ));
    }

    public function show(
        Request $request,
        string $fulfillmentPublicId,
        CurrentOrganization $current,
    ): SalesFulfillmentResource {
        $organization = $current->for($request->user());

        return new SalesFulfillmentResource(
            SalesFulfillment::query()
                ->with($this->relations())
                ->where('organization_id', $organization->id)
                ->where('public_id', $fulfillmentPublicId)
                ->firstOrFail(),
        );
    }

    public function dispatch(
        SalesFulfillmentActionRequest $request,
        string $fulfillmentPublicId,
        CurrentOrganization $current,
        SalesFulfillmentDispatcher $dispatcher,
    ): SalesFulfillmentResource {
        $organization = $current->for($request->user());

        return new SalesFulfillmentResource($dispatcher->dispatch(
            $organization,
            $request->user(),
            $fulfillmentPublicId,
            $request->idempotencyKey(),
        ));
    }

    public function reverse(
        SalesFulfillmentActionRequest $request,
        string $fulfillmentPublicId,
        CurrentOrganization $current,
        SalesFulfillmentReversalService $reversal,
    ): SalesFulfillmentResource {
        $organization = $current->for($request->user());

        return new SalesFulfillmentResource($reversal->reverse(
            $organization,
            $request->user(),
            $fulfillmentPublicId,
            $request->idempotencyKey(),
            trim((string) $request->header('X-Privileged-Reason')),
        ));
    }

    private function relations(): array
    {
        return [
            'order', 'location', 'movement', 'reversalMovement',
            'lines.reservation', 'lines.product', 'lines.lot',
        ];
    }
}
