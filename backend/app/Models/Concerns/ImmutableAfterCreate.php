<?php

namespace App\Models\Concerns;

use LogicException;

trait ImmutableAfterCreate
{
    protected static function bootImmutableAfterCreate(): void
    {
        static::updating(function (): never {
            throw new LogicException('Posted inventory ledger records are immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('Posted inventory ledger records are immutable.');
        });
    }
}
