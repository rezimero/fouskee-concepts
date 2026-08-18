<?php

namespace App\Enums;

enum GoodsReceiptStatus: string
{
    case PendingPosting = 'pending_posting';
    case PendingException = 'pending_exception';
    case Posted = 'posted';
    case Rejected = 'rejected';
    case Reversed = 'reversed';
}
