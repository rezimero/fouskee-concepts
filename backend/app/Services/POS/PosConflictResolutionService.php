<?php

namespace App\Services\POS;

class PosConflictResolutionService
{
    public function resolve(array $conflicts): array
    {
        return [
            'resolved' => true,
            'conflicts' => count($conflicts),
        ];
    }
}
