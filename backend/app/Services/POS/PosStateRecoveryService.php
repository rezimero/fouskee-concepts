<?php

namespace App\Services\POS;

class PosStateRecoveryService
{
    public function restore(array $state): array
    {
        return [
            'restored' => true,
            'state' => $state,
        ];
    }
}
