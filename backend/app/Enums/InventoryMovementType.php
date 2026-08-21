<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Receipt = 'receipt';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';
    case SaleIssue = 'sale_issue';
    case Reversal = 'reversal';
}
