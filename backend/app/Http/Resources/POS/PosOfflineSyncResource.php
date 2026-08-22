<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosOfflineSyncResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'sync_status' => 'ready',
        ];
    }
}
