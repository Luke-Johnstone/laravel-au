<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Support;

/**
 * An Australian state or mainland territory, with the postcode ranges that belong to it.
 */
enum State: string
{
    case NSW = 'NSW';
    case VIC = 'VIC';
    case QLD = 'QLD';
    case SA = 'SA';
    case WA = 'WA';
    case TAS = 'TAS';
    case NT = 'NT';
    case ACT = 'ACT';

    public function label(): string
    {
        return match ($this) {
            self::NSW => 'New South Wales',
            self::VIC => 'Victoria',
            self::QLD => 'Queensland',
            self::SA => 'South Australia',
            self::WA => 'Western Australia',
            self::TAS => 'Tasmania',
            self::NT => 'Northern Territory',
            self::ACT => 'Australian Capital Territory',
        };
    }

    /**
     * Match a code or full name in any case, ignoring surrounding whitespace (including
     * non-breaking spaces) and one trailing dot, so "nsw", "New South Wales" and "Vic." all
     * work. Returns null when nothing matches.
     */
    public static function fromLoose(?string $input): ?self
    {
        if ($input === null) {
            return null;
        }

        $needle = strtoupper(Digits::trim($input));

        if (str_ends_with($needle, '.')) {
            $needle = substr($needle, 0, -1);
        }

        foreach (self::cases() as $state) {
            if ($needle === $state->value || $needle === strtoupper($state->label())) {
                return $state;
            }
        }

        return null;
    }

    /**
     * The state a postcode falls in, or null when it's in no state's range.
     *
     * Strings must be exactly 4 digits after trimming whitespace. Ints are treated as
     * zero-padded, because an int can't hold a leading zero: 800 means "0800". Don't write a
     * leading-zero literal: PHP reads 0200 as octal 128.
     */
    public static function fromPostcode(string|int $postcode): ?self
    {
        $number = self::postcodeNumber($postcode);

        if ($number === null) {
            return null;
        }

        foreach (self::cases() as $state) {
            foreach ($state->postcodeRanges() as [$first, $last]) {
                if ($number >= $first && $number <= $last) {
                    return $state;
                }
            }
        }

        return null;
    }

    /**
     * Inclusive postcode ranges. They show a postcode is plausible for the state, not that it
     * exists. Written as ints, so "0200" is 200: a literal 0200 would be octal.
     *
     * @return non-empty-list<array{0: int, 1: int}>
     */
    public function postcodeRanges(): array
    {
        return match ($this) {
            self::NSW => [[1000, 1999], [2000, 2599], [2619, 2899], [2921, 2999]],
            self::VIC => [[3000, 3999], [8000, 8999]],
            self::QLD => [[4000, 4999], [9000, 9999]],
            self::SA => [[5000, 5999]],
            self::WA => [[6000, 6999]],
            self::TAS => [[7000, 7999]],
            self::NT => [[800, 999]],
            self::ACT => [[200, 299], [2600, 2618], [2900, 2920]],
        };
    }

    /**
     * Codes mapped to labels, in case order, e.g. for a select field.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $state) {
            $options[$state->value] = $state->label();
        }

        return $options;
    }

    private static function postcodeNumber(string|int $postcode): ?int
    {
        if (is_int($postcode)) {
            return $postcode >= 0 && $postcode <= 9999 ? $postcode : null;
        }

        $postcode = Digits::trim($postcode);

        return preg_match('/\A[0-9]{4}\z/', $postcode) === 1 ? (int) $postcode : null;
    }
}
