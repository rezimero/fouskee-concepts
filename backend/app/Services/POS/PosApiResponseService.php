<?php

namespace App\Services\POS;

class PosApiResponseService
{
    public function success(mixed $data): array
    {
        return [
            'success' => true,
            'data' => $data,
        ];
    }
}
