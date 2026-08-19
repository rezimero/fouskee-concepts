<?php

namespace App\Services\Sales;

use App\Models\Organization;
use Illuminate\Support\Str;

class SalesReferenceNumber
{
    public function order(Organization $organization): string
    {
        return 'SO-'
            .strtoupper($organization->code)
            .'-'.now()->format('YmdHis')
            .'-'.Str::upper(Str::random(5));
    }
}
