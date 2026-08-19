<?php

namespace App\Services\Sales;

use App\Models\Organization;
use Illuminate\Support\Str;

class SalesReferenceNumber
{
    public function order(Organization $organization): string
    {
        return $this->make('SO', $organization);
    }

    public function quotation(Organization $organization): string
    {
        return $this->make('QT', $organization);
    }

    private function make(string $prefix, Organization $organization): string
    {
        return $prefix
            .'-'.strtoupper($organization->code)
            .'-'.now()->format('YmdHis')
            .'-'.Str::upper(Str::random(5));
    }
}
