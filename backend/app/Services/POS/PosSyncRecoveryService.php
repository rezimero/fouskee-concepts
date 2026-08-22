<?php

namespace App\Services\POS;

class PosSyncRecoveryService
{
    public function recover(array $payload): array
    {
        return [
            'recovered' => true,
            'payload' => $payload,
        ];
    }
}
