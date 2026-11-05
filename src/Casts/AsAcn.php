<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Casts;

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Acn;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores an ACN as its 9 digits and reads it back as an Acn.
 *
 * @implements CastsAttributes<Acn, Acn|string>
 */
final class AsAcn implements CastsAttributes, ComparesCastableAttributes, SerializesCastableAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidValueException when the stored value isn't valid
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Acn
    {
        return $value === null ? null : self::parse($value);
    }

    /**
     * Accepts an Acn, or a string in any form Acn::from() reads, such as "004 085 616".
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
    private static function parse(mixed $value): Acn
    {
        return $value instanceof Acn ? $value : (self::tryParse($value) ?? throw InvalidValueException::acn());
    }

    /**
     * A string, or an int from an integer column, as an Acn. Null when it isn't valid.
     */
    private static function tryParse(mixed $value): ?Acn
    {
        return is_string($value) || is_int($value) ? Acn::tryFrom((string) $value) : null;
    }
}
