<?php

namespace App\Services\POS;

use App\Models\PosTransaction;

class PosSalesHistoryService
{
    public function history()
    {
        return PosTransaction::query()
            ->latest()
            ->get();
    }
}
