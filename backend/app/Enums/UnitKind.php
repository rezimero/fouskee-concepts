<?php

namespace App\Enums;

enum UnitKind: string
{
    case Mass = 'mass';
    case Count = 'count';
    case Volume = 'volume';
    case Package = 'package';
}
