<?php

namespace App\Services\POS;

class PosFrontendWorkflowService
{
    public function checkoutPayload(array $data): array
    {
        return [
            'cart' => $data['cart'] ?? [],
            'payment' => $data['payment'] ?? [],
            'customer' => $data['customer'] ?? null,
        ];
    }
}
