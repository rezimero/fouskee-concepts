<?php

namespace App\Services\POS;

class PosTransactionConsistencyService
{
    public function validate(array $transaction): array
    {
        return [
            'consistent' => true,
            'transaction' => $transaction,
        ];
    }
}
