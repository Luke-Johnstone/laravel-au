<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Support;

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use JsonSerializable;
use Stringable;

/**
 * An Australian mobile, landline or 13/1300/1800 service number, stored as national digits.
 */
final readonly class PhoneNumber implements JsonSerializable, Stringable
{
    private function __construct(
        /** The national digits, with the trunk 0 for mobiles and landlines, e.g. "0491570156". */
        public string $value,
        private PhoneType $type,
    ) {}

    /**
     * @throws InvalidValueException
     */
    public static function from(string $input): self
    {
        return self::tryFrom($input) ?? throw InvalidValueException::phone();
    }

    /**
     * Accepts national and +61 forms with spaces, hyphens, dots, parentheses and non-breaking
     * spaces, including "+61 (0)2 5550 1234". Returns null for null, blank or invalid input,
     * and for numbers from other countries.
     */
    public static function tryFrom(?string $input): ?self
    {
        $number = $input === null ? null : self::nationalDigits($input);
        $type = $number === null ? null : self::classify($number);

        return $number !== null && $type !== null ? new self($number, $type) : null;
    }

    public static function isValid(?string $input): bool
    {
        return self::tryFrom($input) !== null;
    }

    public function type(): PhoneType
    {
        return $this->type;
    }

    public function isMobile(): bool
    {
        return $this->type === PhoneType::Mobile;
    }

    public function isLandline(): bool
    {
        return $this->type === PhoneType::Landline;
    }

    public function isService(): bool
    {
        return $this->type === PhoneType::Service;
    }

    /**
     * The landline area code, e.g. "02". Null for mobiles and service numbers.
     */
    public function areaCode(): ?string
    {
        return $this->isLandline() ? substr($this->value, 0, 2) : null;
    }

    /**
     * The number in E.164 form, e.g. "+61491570156". Null for service numbers, which can't be
     * dialled from overseas.
     */
    public function toE164(): ?string
    {
        return $this->isService() ? null : '+61'.substr($this->value, 1);
    }

    /**
     * The number as it's usually written in Australia: "0491 570 156", "(02) 5550 1234",
     * "1800 160 401" or "13 12 34".
     */
    public function format(): string
    {
        $local = substr($this->value, 2);

        return match ($this->type) {
            PhoneType::Mobile => self::group($this->value, 4, 3, 3),
            PhoneType::Landline => '('.substr($this->value, 0, 2).') '.self::group($local, 4, 4),
            PhoneType::Service => strlen($this->value) === 6
                ? self::group($this->value, 2, 2, 2)
                : self::group($this->value, 4, 3, 3),
        };
    }

    /**
     * The number as it's written for overseas callers: "+61 491 570 156" or "+61 2 5550 1234".
     * Null for service numbers.
     */
    public function formatInternational(): ?string
    {
        $withoutTrunk = substr($this->value, 1);

        return match ($this->type) {
            PhoneType::Mobile => '+61 '.self::group($withoutTrunk, 3, 3, 3),
            PhoneType::Landline => '+61 '.self::group($withoutTrunk, 1, 4, 4),
            PhoneType::Service => null,
        };
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * Strip separators and turn a +61 or 61 prefix into the trunk 0. Returns null for any other
     * leading "+", including +61 followed by the wrong number of digits.
     */
    private static function nationalDigits(string $input): ?string
    {
        $number = preg_replace('/[ .\-()\x{00A0}]/u', '', $input);

        if ($number === null) {
            return null;
        }

        // "+61 (0)2 5550 1234" arrives as "+610255501234": the trunk 0 is already there.
        if (preg_match('/\A\+?61(0[0-9]{9})\z/', $number, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/\A\+?61([0-9]{9})\z/', $number, $matches) === 1) {
            return '0'.$matches[1];
        }

        return str_starts_with($number, '+') ? null : $number;
    }

    private static function classify(string $number): ?PhoneType
    {
        return match (true) {
            preg_match('/\A04[0-9]{8}\z/', $number) === 1 => PhoneType::Mobile,
            preg_match('/\A0[2378][0-9]{8}\z/', $number) === 1 => PhoneType::Landline,
            preg_match('/\A(?:1[38]00[0-9]{6}|13[0-9]{4})\z/', $number) === 1 => PhoneType::Service,
            default => null,
        };
    }

    /**
     * Split digits into space-separated groups of the given sizes.
     */
    private static function group(string $digits, int ...$sizes): string
    {
        $groups = [];
        $offset = 0;

        foreach ($sizes as $size) {
            $groups[] = substr($digits, $offset, $size);
            $offset += $size;
        }

        return implode(' ', $groups);
    }
}
