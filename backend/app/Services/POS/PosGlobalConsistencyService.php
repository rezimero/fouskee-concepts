<?php

namespace App\Services\POS;

class PosGlobalConsistencyService
{
    public function coordinate(array $terminals): array
    {
        return [
            'consistency_status' => 'synchronized',
            'terminals' => $terminals,
        ];
    }
}
