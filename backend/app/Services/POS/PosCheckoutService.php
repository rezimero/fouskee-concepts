<?php

namespace App\Services\POS;

use App\Models\PosTransaction;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class PosCheckoutService
{
    public function checkout(PosTransaction $transaction): PosTransaction
    {
        return DB::transaction(function () use ($transaction) {
            if ($transaction->status === 'completed') {
                return $transaction;
            }

            $transaction->update([
                'status' => 'completed',
            ]);

            return $transaction->refresh();
        });
    }
}
