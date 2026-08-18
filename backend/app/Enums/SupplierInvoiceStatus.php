<?php

namespace App\Enums;

enum SupplierInvoiceStatus: string
{
    case Recorded = 'recorded';
    case Matched = 'matched';
    case WithinTolerance = 'within_tolerance';
    case Exception = 'exception';
    case ExceptionApproved = 'exception_approved';

    public function eligibleForPayment(): bool
    {
        return in_array($this, [
            self::Matched,
            self::WithinTolerance,
            self::ExceptionApproved,
        ], true);
    }
}
