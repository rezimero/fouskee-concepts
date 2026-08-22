<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Services\POS\PosSalesOrderBridge;
use Illuminate\Http\JsonResponse;

class PosCommercialController extends Controller
{
    public function checkout(PosTransaction $transaction, PosSalesOrderBridge $bridge): JsonResponse
    {
        return response()->json($bridge->convert($transaction));
    }
}
