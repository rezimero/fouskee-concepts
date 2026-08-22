<?php

namespace App\Services\POS;

class PosStateSyncService
{
    public function sync(array $state): array
    {
        return [
            'synced' => true,
            'state' => $state,
        ];
    }
}
