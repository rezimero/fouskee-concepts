<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Services\POS\PosCheckoutService;
use Illuminate\Http\JsonResponse;

class PosCheckoutController extends Controller
{
    public function __invoke(
        PosTransaction $transaction,
        PosCheckoutService $checkout,
    ): JsonResponse {
        return response()->json(
            $checkout->checkout($transaction)
        );
    }
}
