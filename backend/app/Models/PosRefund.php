<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosRefund extends Model
{
    protected $fillable = [
        'pos_transaction_id',
        'amount',
        'status',
        'reason',
    ];
}
