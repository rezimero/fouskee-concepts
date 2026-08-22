<?php

namespace App\Services\POS;

class PosTerminalCoordinationService
{
    public function coordinate(array $terminalState): array
    {
        return [
            'coordinated' => true,
            'terminal_state' => $terminalState,
        ];
    }
}
