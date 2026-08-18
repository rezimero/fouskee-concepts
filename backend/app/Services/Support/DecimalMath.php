<?php

namespace App\Services\Support;

use Illuminate\Support\Facades\DB;

class DecimalMath
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
        return $this->scalar('CAST(-CAST(? AS DECIMAL(30,6)) AS CHAR)', [$value]);
    }

    public function absolute(string|int|float $value): string
    {
        return $this->scalar('CAST(ABS(CAST(? AS DECIMAL(30,6))) AS CHAR)', [$value]);
    }

    public function add(string|int|float $left, string|int|float $right): string
    {
        return $this->scalar(
            'CAST(CAST(? AS DECIMAL(30,6)) + CAST(? AS DECIMAL(30,6)) AS CHAR)',
            [$left, $right],
        );
    }

    public function subtract(string|int|float $left, string|int|float $right): string
    {
        return $this->add($left, $this->negate($right));
    }

    public function percentOf(string|int|float $value, string|int|float $percent): string
    {
        return $this->divide($this->multiply($value, $percent), 100);
    }

    public function isNegative(string|int|float $value): bool
    {
        return $this->compare($value, 0) < 0;
    }

    public function isPositive(string|int|float $value): bool
    {
        return $this->compare($value, 0) > 0;
    }

    public function isZero(string|int|float $value): bool
    {
        return $this->compare($value, 0) === 0;
    }

    public function greaterThan(string|int|float $left, string|int|float $right): bool
    {
        return $this->compare($left, $right) > 0;
    }

    public function greaterThanOrEqual(
        string|int|float $left,
        string|int|float $right,
    ): bool {
        return $this->compare($left, $right) >= 0;
    }

    private function compare(string|int|float $left, string|int|float $right): int
    {
        $row = DB::selectOne(
            'SELECT CASE '
            .'WHEN CAST(? AS DECIMAL(30,6)) < CAST(? AS DECIMAL(30,6)) THEN -1 '
            .'WHEN CAST(? AS DECIMAL(30,6)) > CAST(? AS DECIMAL(30,6)) THEN 1 '
            .'ELSE 0 END AS value',
            [$left, $right, $left, $right],
        );

        return (int) $row->value;
    }

    private function scalar(string $expression, array $bindings): string
    {
        $row = DB::selectOne("SELECT {$expression} AS value", $bindings);

        return (string) $row->value;
    }
}
