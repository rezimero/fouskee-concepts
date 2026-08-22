<?php

namespace App\Services\POS;

class PosIntegrityCheckService
{
    public function verify(): array
    {
        return [
            'status' => 'verified',
        ];
    }
}
