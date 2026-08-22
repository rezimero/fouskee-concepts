<?php

namespace App\Services\POS;

use App\Models\PosTransaction;
use Illuminate\Support\Facades\DB;

class PosCompletionService
{
    public function complete(PosTransaction $transaction): PosTransaction
    {
        return DB::transaction(function () use ($transaction) {
            if ($transaction->status !== 'completed') {
                $transaction->update([
                    'status' => 'completed',
                ]);
            }

            return $transaction->refresh();
        });
    }
}
