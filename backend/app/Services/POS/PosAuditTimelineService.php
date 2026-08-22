<?php

namespace App\Services\POS;

class PosAuditTimelineService
{
    public function record(string $event, array $context = []): array
    {
        return [
            'event' => $event,
            'context' => $context,
        ];
    }
}
