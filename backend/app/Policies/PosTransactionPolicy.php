<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PosTransaction;

class PosTransactionPolicy
{
    public function complete(User $user, PosTransaction $transaction): bool
    {
        return true;
    }
}
