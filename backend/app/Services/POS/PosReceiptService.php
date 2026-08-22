<?php

namespace App\\Services\\POS;

use App\\Models\\PosReceipt;
use App\\Models\\PosTransaction;

class PosReceiptService
{
    public function generate(PosTransaction $transaction): PosReceipt
    {
        return PosReceipt::firstOrCreate(
            [
                'pos_transaction_id' => $transaction->id,
            ],
            [
                'receipt_number' => 'RCT-' . $transaction->id,
                'payload' => [
                    'transaction_number' => $transaction->transaction_number,
                    'total_amount' => $transaction->total_amount,
                ],
            ]
        );
    }
}
