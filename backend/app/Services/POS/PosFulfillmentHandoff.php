<?php

namespace App\Services\POS;

use App\Models\PosTransaction;
use App\Models\SalesFulfillment;

class PosFulfillmentHandoff
{
    public function ready(PosTransaction $transaction): bool
    {
        return $transaction->status === 'completed';
    }

    public function fulfillmentRequired(PosTransaction $transaction): array
    {
        return [
            'pos_transaction_id' => $transaction->id,
            'sales_order_id' => $transaction->sales_order_id,
            'ready' => $this->ready($transaction),
        ];
    }
}
