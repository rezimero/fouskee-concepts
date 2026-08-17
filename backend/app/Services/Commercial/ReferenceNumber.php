<?php

namespace App\Services\Commercial;

use Illuminate\Support\Str;

class ReferenceNumber
{
    public function customer(): string
    {
        return $this->make('CUS');
    }

    public function supplier(): string
    {
        return $this->make('SUP');
    }

    private function make(string $prefix): string
    {
        return $prefix.'-'.strtoupper(substr((string) Str::ulid(), -10));
    }
}
