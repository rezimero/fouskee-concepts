<?php

namespace App\Services\POS;

class PosFrontendContractService
{
    public function payload(array $data): array
    {
        return [
            'data' => $data,
            'version' => 'v0.4O',
        ];
    }
}
