<?php

namespace App\Services\Inventory;

use App\Models\Organization;
use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPostingContextFactory
{
    public function __construct(
        private readonly InventoryBackdatePolicy $backdates,
    ) {}

    public function make(
        Organization $organization,
        User $actor,
        StockTransfer $transfer,
        ?string $occurredAt,
        ?string $reason,
    ): InventoryPostingContext {
        return new InventoryPostingContext(
            reason: $reason,
            occurredAt: $this->backdates->occurredAt(
                $organization,
                $actor,
                $occurredAt,
                $reason,
            ),
            sourceReferenceType: 'stock_transfer',
            sourceReferencePublicId: $transfer->public_id,
        );
    }
}
