<?php

namespace App\Services\POS;

class PosOfflineQueueService
{
    public function queue(array $payload): array
    {
        return [
            'queued' => true,
            'payload' => $payload,
        ];
    }
}
