<?php

namespace App\Services\POS;

class PosAuthorizationService
{
    public function canPerform(string $action, array $abilities): bool
    {
        return in_array($action, $abilities, true);
    }
}
