<?php

namespace App\Services\POS;

class PosTransactionCalculator
{
    public function total(array $lines): string
    {
        $total = '0.000000';

        foreach ($lines as $line) {
            $total = bcadd(
                $total,
                bcmul((string)$line['quantity'], (string)$line['unit_price'], 6),
                6
            );
        }

        return $total;
    }
}
