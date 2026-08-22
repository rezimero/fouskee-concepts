<?php

namespace App\Services\POS;

class PosCheckpointReconciliationService
{
    public function reconcile(array $checkpoints): array
    {
        return [
            'reconciled' => true,
            'checkpoint_count' => count($checkpoints),
        ];
    }
}
