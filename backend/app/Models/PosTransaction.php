<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    protected $fillable = [
        'organization_id',
        'customer_id',
        'sales_order_id',
        'status',
        'transaction_number',
        'total_amount',
    ];
}
