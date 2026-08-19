<?php

namespace App\Enums;

enum SalesQuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Converted = 'converted';
    case Cancelled = 'cancelled';
}
