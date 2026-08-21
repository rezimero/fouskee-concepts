<?php

namespace App\Enums;

enum SalesFulfillmentStatus: string
{
    case Draft = 'draft';
    case Picked = 'picked';
    case Dispatched = 'dispatched';
    case Reversed = 'reversed';
}
