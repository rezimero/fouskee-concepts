<?php

namespace App\Services\Procurement;

use App\Services\Support\DecimalMath;

class ProcurementTolerance
{
    public function __construct(
        private readonly DecimalMath $math,
    ) {}

    public function exceedsUpper(
        string $actual,
        string $reference,
        string $percent,
    ): bool {
        $allowed = $this->math->add(
            $reference,
            $this->math->percentOf($reference, $percent),
        );

        return $this->math->greaterThan($actual, $allowed);
    }

    public function exceedsAbsolute(
        string $actual,
        string $reference,
        string $percent,
    ): bool {
        $difference = $this->math->absolute(
            $this->math->subtract($actual, $reference),
        );
        $allowed = $this->math->absolute(
            $this->math->percentOf($reference, $percent),
        );

        return $this->math->greaterThan($difference, $allowed);
    }
}
