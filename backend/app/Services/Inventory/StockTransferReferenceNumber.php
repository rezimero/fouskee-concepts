<?php

namespace App\Services\Inventory;

use App\Models\Organization;
use Illuminate\Support\Str;

class StockTransferReferenceNumber
{
    public function next(Organization $organization): string
    {
        return 'TRF-'
            .strtoupper($organization->code)
            .'-'.now()->format('YmdHis')
            .'-'.Str::upper(Str::random(5));
    }
}
