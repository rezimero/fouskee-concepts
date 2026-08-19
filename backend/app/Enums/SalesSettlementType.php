<?php

namespace App\Enums;

enum SalesSettlementType: string
{
    case Prepaid = 'prepaid';
    case Credit = 'credit';
}
