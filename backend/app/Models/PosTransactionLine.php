<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTransactionLine extends Model
{
    protected $fillable = [
        'pos_transaction_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
    ];
}
