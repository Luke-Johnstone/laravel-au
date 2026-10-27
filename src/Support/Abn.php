<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Support;

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use JsonSerializable;
use Stringable;

/**
 * An Australian Business Number: 11 digits protected by a modulus 89 checksum.
 */
final readonly class Abn implements JsonSerializable, Stringable
{
    private const int LENGTH = 11;

    /** Checksum weights, applied after subtracting 1 from the first digit. */
    private const array WEIGHTS = [10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19];

    private function __construct(
        /** The 11 digits, without separators. */
        public string $value,
    ) {}

    /**
     * @throws InvalidValueException
     */
    public static function from(string $input): self
    {
        return self::tryFrom($input) ?? throw InvalidValueException::abn();
    }

    /**
     * Spaces, hyphens, dots and non-breaking spaces are ignored. Returns null for null, blank
     * or invalid input.
     */
    public static function tryFrom(?string $input): ?self
    {
        $digits = $input === null ? null : Digits::normalise($input, self::LENGTH);

        return $digits !== null && self::hasValidChecksum($digits) ? new self($digits) : null;
    }

    public static function isValid(?string $input): bool
    {
        return self::tryFrom($input) !== null;
    }

    /**
     * The ABN in its usual 2-3-3-3 grouping, e.g. "51 824 753 556".
     */
    public function format(): string
    {
        return substr($this->value, 0, 2).' '.implode(' ', str_split(substr($this->value, 2), 3));
    }

    /**
     * The ACN in the last nine digits, when they form a valid ACN.
     *
     * A company's ABN is usually two check digits followed by its ACN. This is a heuristic,
     * not a register lookup: a match doesn't prove the ABN and ACN belong to the same entity.
     */
    public function acn(): ?Acn
    {
        return Acn::tryFrom(substr($this->value, 2));
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

    private static function hasValidChecksum(string $digits): bool
    {
        $sum = 0;

        foreach (self::WEIGHTS as $position => $weight) {
            $digit = (int) $digits[$position];
            $sum += ($position === 0 ? $digit - 1 : $digit) * $weight;
        }

        return $sum % 89 === 0;
    }
}
