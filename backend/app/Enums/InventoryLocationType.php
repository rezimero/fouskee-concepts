<?php

namespace App\Enums;

enum InventoryLocationType: string
{
    case Warehouse = 'warehouse';
    case Store = 'store';
    case Transit = 'transit';
}
