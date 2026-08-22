<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class PosReceipt extends Model
{
    protected $fillable = [
        'pos_transaction_id',
        'receipt_number',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
