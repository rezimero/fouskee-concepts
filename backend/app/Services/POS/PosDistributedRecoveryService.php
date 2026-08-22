<?php

namespace App\Services\POS;

class PosDistributedRecoveryService
{
    public function recover(array $nodes): array
    {
        return [
            'nodes_recovered' => count($nodes),
        ];
    }
}
