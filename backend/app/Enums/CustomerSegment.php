<?php

namespace App\Enums;

enum CustomerSegment: string
{
    case Wholesale = 'wholesale';
    case Retail = 'retail';
    case Hybrid = 'hybrid';
}
