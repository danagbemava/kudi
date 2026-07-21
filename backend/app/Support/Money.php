<?php

namespace App\Support;

class Money
{
    /**
     * Processing fee for a gross amount, both in integer minor units.
     *
     * Pure integer math (no float involvement) with half-up rounding:
     * fee = round(gross * bp / 10000). Adding half the divisor before the
     * integer division implements half-up without touching floats.
     */
    public static function fee(int $gross): int
    {
        $basisPoints = (int) config('kudi.fee_basis_points', 150);

        return intdiv($gross * $basisPoints + 5_000, 10_000);
    }
}
