<?php

namespace App\Services\POS;

use App\Models\PosTransaction;

class PosLifecycleGuard
{
    public function canTransition(PosTransaction $transaction, string $state): bool
    {
        return $transaction->status !== $state;
    }
}
