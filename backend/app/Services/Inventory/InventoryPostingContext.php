<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use Carbon\CarbonImmutable;

final readonly class InventoryPostingContext
{
    public function __construct(
        public ?string $reason = null,
        public ?CarbonImmutable $occurredAt = null,
        public ?string $sourceReferenceType = null,
        public ?string $sourceReferencePublicId = null,
        public ?InventoryMovement $reversalOf = null,
    ) {}
}
