<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Support;

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use JsonSerializable;
use Stringable;

/**
 * A bank-state-branch number: 6 digits identifying an Australian bank branch.
 *
 * Only the format is checked. BSBs have no checksum, and the BSB directory isn't bundled, so
 * a valid BSB may not belong to an open branch.
 */
final readonly class Bsb implements JsonSerializable, Stringable
{
    private const int LENGTH = 6;

    private function __construct(
        /** The 6 digits, without separators. */
        public string $value,
    ) {}

    /**
     * @throws InvalidValueException
     */
    public static function from(string $input): self
    {
        return self::tryFrom($input) ?? throw InvalidValueException::bsb();
    }

    /**
     * Spaces, hyphens, dots and non-breaking spaces are ignored. Returns null for null, blank
     * or invalid input.
     */
    public static function tryFrom(?string $input): ?self
    {
        $digits = $input === null ? null : Digits::normalise($input, self::LENGTH);

        return $digits !== null ? new self($digits) : null;
    }

    public static function isValid(?string $input): bool
    {
        return self::tryFrom($input) !== null;
    }

    /**
     * The BSB in its usual 3-3 grouping, e.g. "062-000".
     */
    public function format(): string
    {
        return substr($this->value, 0, 3).'-'.substr($this->value, 3);
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
}
