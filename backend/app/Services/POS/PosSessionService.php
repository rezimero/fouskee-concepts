<?php

namespace App\Services\POS;

use App\Models\PosSession;

class PosSessionService
{
    public function open(array $data): PosSession
    {
        return PosSession::create($data + [
            'status' => 'open',
        ]);
    }

    public function close(PosSession $session, array $data): PosSession
    {
        $session->update($data + [
            'status' => 'closed',
        ]);

        return $session->refresh();
    }
}
