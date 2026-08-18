<?php

namespace App\Enums;

enum InventoryLocationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
