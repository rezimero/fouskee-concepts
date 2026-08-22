<?php

namespace App\Services\POS;

class PosConnectionRecoveryService
{
    public function reconnect(array $state): array
    {
        return [
            'reconnected' => true,
            'state' => $state,
        ];
    }
}
