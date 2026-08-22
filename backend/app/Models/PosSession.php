<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosSession extends Model
{
    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'opening_balance',
        'closing_balance',
        'status',
    ];
}
