<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\ReserveSalesOrderRequest;
use App\Services\Organization\CurrentOrganization;
use App\Services\Sales\SalesOrderReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesOrderReservationController extends Controller
{
    public function show(
        Request $request,
        string $salesOrderPublicId,
        CurrentOrganization $current,
        SalesOrderReservationService $service,
    ): JsonResponse {
        $organization = $current->for($request->user());

        return response()->json([
            'data' => $service->summary($organization, $salesOrderPublicId),
        ]);
    }

    public function reserve(
        ReserveSalesOrderRequest $request,
        string $salesOrderPublicId,
        CurrentOrganization $current,
        SalesOrderReservationService $service,
    ): JsonResponse {
        $organization = $current->for($request->user());

        return response()->json([
            'data' => $service->reserve(
                $organization,
                $request->user(),
                $salesOrderPublicId,
                $request->validated('location_id'),
            ),
        ]);
    }

    public function release(
        Request $request,
        string $salesOrderPublicId,
        CurrentOrganization $current,
        SalesOrderReservationService $service,
    ): JsonResponse {
        $organization = $current->for($request->user());
        $reason = trim((string) $request->header('X-Privileged-Reason'));

        return response()->json([
            'data' => $service->release(
                $organization,
                $request->user(),
                $salesOrderPublicId,
                $reason,
            ),
        ]);
    }
}
