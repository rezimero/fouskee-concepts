<?php

namespace App\Enums;

enum StockCountStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Posted = 'posted';
    case Rejected = 'rejected';
}
