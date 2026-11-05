<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Casts;

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Postcode;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a postcode as its 4 digits and reads it back as a Postcode.
 *
 * @implements CastsAttributes<Postcode, Postcode|string>
 */
final class AsPostcode implements CastsAttributes, ComparesCastableAttributes, SerializesCastableAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidValueException when the stored value isn't valid
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Postcode
    {
        return $value === null ? null : self::parse($value);
    }

    /**
     * Accepts a Postcode, or a string in any form Postcode::from() reads, such as "2600".
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidValueException when the value isn't valid
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : self::parse($value)->value;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $this->set($model, $key, $value, $attributes);
    }

    /**
     * Stored values are the same when they hold the same postcode, so reading a value stored in
     * another form doesn't mark the model as changed.
     */
    public function compare(Model $model, string $key, mixed $firstValue, mixed $secondValue): bool
    {
        $first = self::tryParse($firstValue);
        $second = self::tryParse($secondValue);

        return $first !== null && $second !== null && $first->equals($second);
    }

    /**
     * @throws InvalidValueException
     */
    private static function parse(mixed $value): Postcode
    {
        return $value instanceof Postcode ? $value : (self::tryParse($value) ?? throw InvalidValueException::postcode());
    }

    /**
     * A string, or an int from an integer column, as a Postcode. Null when it isn't valid.
     */
    private static function tryParse(mixed $value): ?Postcode
    {
        return is_string($value) || is_int($value) ? Postcode::tryFrom((string) $value) : null;
    }
}
