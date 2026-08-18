<?php

namespace App\Services\Procurement;

use App\Models\Organization;
use Illuminate\Support\Str;

class ProcurementReferenceNumber
{
    public function purchaseOrder(Organization $organization): string
    {
        return $this->make('PO', $organization);
    }

    public function goodsReceipt(Organization $organization): string
    {
        return $this->make('GRN', $organization);
    }

    private function make(string $prefix, Organization $organization): string
    {
        return $prefix
            .'-'.strtoupper($organization->code)
            .'-'.now()->format('YmdHis')
            .'-'.Str::upper(Str::random(5));
    }
}
