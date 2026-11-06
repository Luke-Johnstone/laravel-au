<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament;

use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Bsb;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\State;

/**
 * Formats stored values for the table columns and infolist entries. Each accepts the value
 * object (from a cast), a stored string, or an int from an integer column, and returns anything
 * it can't read unchanged, so a bad value is shown as it is rather than hidden.
 *
 * @internal
 */
final class Display
{
    private function __construct() {}

    public static function abn(mixed $state): mixed
    {
        return self::toAbn($state)?->format() ?? $state;
    }

    public static function acn(mixed $state): mixed
    {
        $acn = $state instanceof Acn ? $state : Acn::tryFrom(self::text($state));

        return $acn?->format() ?? $state;
    }

    public static function bsb(mixed $state): mixed
    {
        $bsb = $state instanceof Bsb ? $state : Bsb::tryFrom(self::text($state));

        return $bsb?->format() ?? $state;
    }

    public static function phone(mixed $state): mixed
    {
        return self::toPhone($state)?->format() ?? $state;
    }

    public static function state(mixed $state, bool $abbreviated): mixed
    {
        $value = $state instanceof State ? $state : State::fromLoose(self::text($state));

        if ($value === null) {
            return $state;
        }

        return $abbreviated ? $value->value : $value->label();
    }

    /**
     * The ABN's page on ABN Lookup, or null when the value isn't a valid ABN. The URL is built
     * from the parsed digits only, never from the stored text.
     */
    public static function abnLookupUrl(mixed $state): ?string
    {
        $abn = self::toAbn($state);

        return $abn === null ? null : "https://abr.business.gov.au/ABN/View?abn={$abn->value}";
    }

    /**
     * A tel: link in E.164, or national digits for a service number (which has no E.164 form).
     * Null when the value isn't a valid phone number.
     */
    public static function telUrl(mixed $state): ?string
    {
        $phone = self::toPhone($state);

        return $phone === null ? null : 'tel:'.($phone->toE164() ?? $phone->value);
    }

    private static function toAbn(mixed $state): ?Abn
    {
        return $state instanceof Abn ? $state : Abn::tryFrom(self::text($state));
    }

    private static function toPhone(mixed $state): ?PhoneNumber
    {
        return $state instanceof PhoneNumber ? $state : PhoneNumber::tryFrom(self::text($state));
    }

    /**
     * A stored string, or an int from an integer column, as text; null for anything else.
     */
    private static function text(mixed $state): ?string
    {
        return is_string($state) || is_int($state) ? (string) $state : null;
    }
}
