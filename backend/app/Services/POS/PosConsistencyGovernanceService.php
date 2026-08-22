<?php

namespace App\Services\POS;

class PosConsistencyGovernanceService
{
    public function govern(array $state): array
    {
        return [
            'governance_status' => 'validated',
            'state' => $state,
        ];
    }
}
