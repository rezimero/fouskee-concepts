<?php

namespace App\Services\POS;

class PosTransactionRecoveryService
{
    public function recover(array $transaction): array
    {
        return [
            'recovered' => true,
            'transaction' => $transaction,
        ];
    }
}
