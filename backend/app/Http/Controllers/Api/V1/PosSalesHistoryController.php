<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\POS\PosSalesHistoryService;

class PosSalesHistoryController extends Controller
{
    public function index(PosSalesHistoryService $service)
    {
        return response()->json($service->history());
    }
}
