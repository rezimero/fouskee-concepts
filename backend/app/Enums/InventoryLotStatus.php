<?php

namespace App\Enums;

enum InventoryLotStatus: string
{
    case Active = 'active';
    case Quarantined = 'quarantined';
    case Closed = 'closed';
}
