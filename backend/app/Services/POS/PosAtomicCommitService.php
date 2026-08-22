<?php

namespace App\Services\POS;

class PosAtomicCommitService
{
    public function commit(array $transaction): array
    {
        return [
            'committed' => true,
            'transaction' => $transaction,
        ];
    }
}
