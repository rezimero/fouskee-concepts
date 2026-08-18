<?php

namespace App\Services\Inventory;

use App\Models\Organization;
use Illuminate\Support\Str;

class InventoryReferenceNumber
{
    public function next(Organization $organization): string
    {
        return 'INV-'
            .strtoupper($organization->code)
            .'-'.now()->format('YmdHis')
            .'-'.Str::upper(Str::random(6));
    }
}
