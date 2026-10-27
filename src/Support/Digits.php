<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Support;

/**
 * Normalises identifier input (ABN, ACN, BSB) to its digits.
 *
 * @internal
 */
final readonly class Digits
{
    private function __construct() {}

    /**
     * Strip spaces, hyphens, dots and non-breaking spaces (U+00A0), then return the digits when
     * exactly `$length` remain. Any other character, including tabs, newlines and other Unicode
     * spaces, makes the input invalid and returns null.
     */
    public static function normalise(string $input, int $length): ?string
    {
        $digits = preg_replace('/[ .\-\x{00A0}]/u', '', $input);

        if ($digits === null || preg_match('/\A[0-9]{'.$length.'}\z/', $digits) !== 1) {
            return null;
        }

        return $digits;
    }
}
