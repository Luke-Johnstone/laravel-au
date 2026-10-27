<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a value object can't parse its input. The message never includes the input,
 * because it may be personal information that would otherwise end up in logs.
 */
final class InvalidValueException extends InvalidArgumentException
{
    public static function abn(): self
    {
        return new self('The given value is not a valid ABN.');
    }

    public static function acn(): self
    {
        return new self('The given value is not a valid ACN.');
    }

    public static function bsb(): self
    {
        return new self('The given value is not a valid BSB.');
    }

    public static function phone(): self
    {
        return new self('The given value is not a valid phone number.');
    }

    public static function postcode(): self
    {
        return new self('The given value is not a valid postcode.');
    }
}
