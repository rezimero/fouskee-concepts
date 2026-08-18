<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Receipt = 'receipt';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';
    case Reversal = 'reversal';
}
