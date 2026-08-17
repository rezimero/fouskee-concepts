<?php

namespace App\Enums;

enum CommercialPartyStatus: string
{
    case Active = 'active';
    case OnHold = 'on_hold';
    case Archived = 'archived';
}
