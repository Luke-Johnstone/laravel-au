<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Support;

/**
 * Input normalisation shared by the value objects.
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

    /**
     * Trim whitespace from both ends, treating a non-breaking space (U+00A0) as whitespace too,
     * because it often comes along when a value is copied from a web page.
     */
    public static function trim(string $input): string
    {
        return trim(str_replace("\u{00A0}", ' ', $input));
    }
}
