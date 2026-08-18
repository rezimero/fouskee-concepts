<?php

namespace App\Services\Inventory;

use App\Models\Organization;
use Illuminate\Support\Str;

class StockCountReferenceNumber
{
    public function next(Organization $organization): string
    {
        return 'CNT-'
            .strtoupper($organization->code)
            .'-'.now()->format('YmdHis')
            .'-'.Str::upper(Str::random(5));
    }
}
