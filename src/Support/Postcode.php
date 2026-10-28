<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Support;

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use JsonSerializable;
use Stringable;

/**
 * An Australian postcode: 4 digits that fall inside a state's postcode ranges.
 *
 * This checks the postcode is plausible, not that it exists. A valid postcode may not be allocated.
 */
final readonly class Postcode implements JsonSerializable, Stringable
{
    private function __construct(
        /** The 4 digits, as a string so leading zeros are kept. */
        public string $value,
        private State $state,
    ) {}

    /**
     * @throws InvalidValueException
     */
    public static function from(string $input): self
    {
        return self::tryFrom($input) ?? throw InvalidValueException::postcode();
    }

    /**
     * Surrounding whitespace, including non-breaking spaces, is ignored. Returns null for null,
     * blank or invalid input, including 4 digits that belong to no state.
     */
    public static function tryFrom(?string $input): ?self
    {
        if ($input === null) {
            return null;
        }

        $value = Digits::trim($input);
        $state = State::fromPostcode($value);

        return $state !== null ? new self($value, $state) : null;
    }

    public static function isValid(?string $input): bool
    {
        return self::tryFrom($input) !== null;
    }

    public function state(): State
    {
        return $this->state;
    }

    public function belongsTo(State $state): bool
    {
        return $this->state === $state;
    }

    public function format(): string
    {
        return $this->value;
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
