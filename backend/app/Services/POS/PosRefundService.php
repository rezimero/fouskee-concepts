<?php

namespace App\Services\POS;

use App\Models\PosRefund;
use App\Models\PosTransaction;
use Illuminate\Support\Facades\DB;

class PosRefundService
{
    public function create(PosTransaction $transaction, array $data): PosRefund
    {
        return DB::transaction(function () use ($transaction, $data) {
            return PosRefund::create([
                'pos_transaction_id' => $transaction->id,
                'amount' => $data['amount'],
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
            ]);
        });
    }
}
