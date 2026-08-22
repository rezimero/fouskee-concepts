<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosCommitIntegrityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'commit_status' => 'valid',
        ];
    }
}
