<?php

namespace App\Services\Sales;

use App\Models\Organization;
use Illuminate\Support\Str;

class SalesFulfillmentReferenceNumber
{
    public function next(Organization $organization): string
    {
        return 'FUL-'
            .strtoupper($organization->code)
            .'-'.now()->format('YmdHis')
            .'-'.Str::upper(Str::random(5));
    }
}
