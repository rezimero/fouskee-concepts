<?php

namespace App\Services\POS;

class PosHealthCheckService
{
    public function check(): array
    {
        return [
            'status' => 'ok',
            'module' => 'pos',
        ];
    }
}
