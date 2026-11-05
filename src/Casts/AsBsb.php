<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Casts;

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Bsb;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a BSB as its 6 digits and reads it back as a Bsb.
 *
 * @implements CastsAttributes<Bsb, Bsb|string>
 */
final class AsBsb implements CastsAttributes, ComparesCastableAttributes, SerializesCastableAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidValueException when the stored value isn't valid
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Bsb
    {
        return $value === null ? null : self::parse($value);
    }

    /**
     * Accepts a Bsb, or a string in any form Bsb::from() reads, such as "062-000".
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
     * Stored values are the same when they hold the same number, so reading a value stored in
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
    private static function parse(mixed $value): Bsb
    {
        return $value instanceof Bsb ? $value : (self::tryParse($value) ?? throw InvalidValueException::bsb());
    }

    /**
     * A string, or an int from an integer column, as a Bsb. Null when it isn't valid.
     */
    private static function tryParse(mixed $value): ?Bsb
    {
        return is_string($value) || is_int($value) ? Bsb::tryFrom((string) $value) : null;
    }
}
