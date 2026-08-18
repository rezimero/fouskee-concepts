<?php

namespace App\Enums;

enum StockTransferStatus: string
{
    case Draft = 'draft';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Cancelled = 'cancelled';
}
