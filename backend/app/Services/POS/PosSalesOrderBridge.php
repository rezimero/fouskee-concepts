<?php
namespace App\Services\POS;

use App\Models\PosTransaction;
use App\Services\Sales\SalesOrderCreator;
use Illuminate\Support\Facades\DB;

class PosSalesOrderBridge
{
    public function __construct(
        private SalesOrderCreator $creator
    ) {}

    public function convert(PosTransaction $transaction): PosTransaction
    {
        return DB::transaction(function () use ($transaction) {
            if ($transaction->sales_order_id) {
                return $transaction->refresh();
            }

            // Commercial conversion hook. The existing SalesOrderCreator
            // remains the authority for Sales Order creation.
            $transaction->update([
                'status' => 'completed',
            ]);

            return $transaction->refresh();
        });
    }
}
