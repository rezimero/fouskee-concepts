<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosPayment extends Model
{
    protected $fillable = [
        'pos_transaction_id',
        'method',
        'amount',
        'status',
    ];
}
