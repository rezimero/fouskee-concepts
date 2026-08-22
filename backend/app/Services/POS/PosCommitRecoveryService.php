<?php

namespace App\Services\POS;

class PosCommitRecoveryService
{
    public function recover(array $transaction): array
    {
        return [
            'recovered' => true,
            'transaction' => $transaction,
        ];
    }
}
