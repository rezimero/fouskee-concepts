<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosTerminalStateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'terminal_status' => 'active',
        ];
    }
}
