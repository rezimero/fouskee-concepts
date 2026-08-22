<?php

namespace App\Services\POS;

class PosDurableStateService
{
    public function persist(array $state): array
    {
        return [
            'persisted' => true,
            'state' => $state,
        ];
    }
}
