<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Support;

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use JsonSerializable;
use Stringable;

/**
 * An Australian Company Number: 9 digits, the last of which is a check digit.
 */
final readonly class Acn implements JsonSerializable, Stringable
{
    private const int LENGTH = 9;

    /** Weights for the first eight digits; the ninth is the check digit. */
    private const array WEIGHTS = [8, 7, 6, 5, 4, 3, 2, 1];

    private function __construct(
        /** The 9 digits, without separators. */
        public string $value,
    ) {}

    /**
     * @throws InvalidValueException
     */
    public static function from(string $input): self
    {
        return self::tryFrom($input) ?? throw InvalidValueException::acn();
    }

    /**
     * Spaces, hyphens, dots and non-breaking spaces are ignored. Returns null for null, blank
     * or invalid input.
     */
    public static function tryFrom(?string $input): ?self
    {
        $digits = $input === null ? null : Digits::normalise($input, self::LENGTH);

        return $digits !== null && self::hasValidCheckDigit($digits) ? new self($digits) : null;
    }

    public static function isValid(?string $input): bool
    {
        return self::tryFrom($input) !== null;
    }

    /**
     * The ACN in its usual 3-3-3 grouping, e.g. "004 085 616".
     */
    public function format(): string
    {
        return implode(' ', str_split($this->value, 3));
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

    private static function hasValidCheckDigit(string $digits): bool
    {
        $sum = 0;

        foreach (self::WEIGHTS as $position => $weight) {
            $sum += (int) $digits[$position] * $weight;
        }

        return (10 - $sum % 10) % 10 === (int) $digits[self::LENGTH - 1];
    }
}
