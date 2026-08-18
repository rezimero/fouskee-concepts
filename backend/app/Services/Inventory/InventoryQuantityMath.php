<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;

class InventoryQuantityMath
{
    public function multiply(string|int|float $left, string|int|float $right): string
    {
        return $this->scalar(
            'CAST(CAST(? AS DECIMAL(30,6)) * CAST(? AS DECIMAL(30,6)) AS CHAR)',
            [$left, $right],
        );
    }

    public function divide(string|int|float $left, string|int|float $right): string
    {
        return $this->scalar(
            'CAST(CAST(? AS DECIMAL(30,6)) / NULLIF(CAST(? AS DECIMAL(30,6)), 0) AS CHAR)',
            [$left, $right],
        );
    }

    public function negate(string|int|float $value): string
    {
        return $this->scalar(
            'CAST(-CAST(? AS DECIMAL(30,6)) AS CHAR)',
            [$value],
        );
    }

    public function add(string|int|float $left, string|int|float $right): string
    {
        return $this->scalar(
            'CAST(CAST(? AS DECIMAL(30,6)) + CAST(? AS DECIMAL(30,6)) AS CHAR)',
            [$left, $right],
        );
    }

    public function isNegative(string|int|float $value): bool
    {
        return str_starts_with(trim((string) $value), '-');
    }

    private function scalar(string $expression, array $bindings): string
    {
        $row = DB::selectOne("SELECT {$expression} AS value", $bindings);

        return (string) $row->value;
    }
}
