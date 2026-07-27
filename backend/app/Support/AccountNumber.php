<?php

namespace App\Support;

use App\Enums\InstitutionType;

/**
 * Where a merchant's money is sent.
 *
 * Validity depends on the institution behind it: a mobile money wallet is
 * addressed by a Ghanaian mobile number, a bank account by a digit string whose
 * length differs between banks. Both are checked for shape only — nothing here
 * proves the account exists, which needs the institution itself. Treat this as
 * catching typos, not as verification.
 */
final class AccountNumber
{
    /**
     * Ghanaian mobile prefixes, national format. MTN 024/025/053/054/055/059,
     * Telecel 020/050, AT 026/027/056/057, plus 023/028/029 in issue.
     */
    private const MOBILE_PATTERN = '/^0(2[0-9]|5[0-9])[0-9]{7}$/';

    /**
     * Ghanaian bank account numbers run from around 10 digits to 16 depending
     * on the bank, so the bound is deliberately loose. Tightening it per bank
     * would need a rule per institution, which is not knowledge this app has.
     */
    private const BANK_PATTERN = '/^[0-9]{8,20}$/';

    /**
     * People paste account numbers with the grouping they were given them in.
     * Strip it once, on the way in, so storage and comparison stay uniform.
     */
    public static function normalise(?string $number): string
    {
        return preg_replace('/[\s\-]/', '', (string) $number) ?? '';
    }

    public static function isValidFor(InstitutionType $type, ?string $number): bool
    {
        $normalised = self::normalise($number);

        return (bool) preg_match(
            $type === InstitutionType::MobileMoney ? self::MOBILE_PATTERN : self::BANK_PATTERN,
            $normalised
        );
    }

    /** What to tell someone who got it wrong, in the terms of their institution. */
    public static function hint(InstitutionType $type): string
    {
        return $type === InstitutionType::MobileMoney
            ? 'Enter the 10-digit mobile money number, starting with 0. For example 024 123 4567.'
            : 'Enter the account number as digits only, between 8 and 20 of them.';
    }
}
