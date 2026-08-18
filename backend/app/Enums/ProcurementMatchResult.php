<?php

namespace App\Enums;

enum ProcurementMatchResult: string
{
    case Matched = 'matched';
    case WithinTolerance = 'within_tolerance';
    case Exception = 'exception';
}
