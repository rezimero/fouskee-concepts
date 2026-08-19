<?php

namespace App\Enums;

enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Cancelled = 'cancelled';
}
