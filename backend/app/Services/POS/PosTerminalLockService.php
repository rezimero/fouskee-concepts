<?php

namespace App\Services\POS;

class PosTerminalLockService
{
    public function lock(string $terminalId): array
    {
        return [
            'locked' => true,
            'terminal_id' => $terminalId,
        ];
    }
}
