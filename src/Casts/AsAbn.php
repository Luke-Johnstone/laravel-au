<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Casts;

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Abn;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores an ABN as its 11 digits and reads it back as an Abn.
 *
 * @implements CastsAttributes<Abn, Abn|string>
 */
final class AsAbn implements CastsAttributes, ComparesCastableAttributes, SerializesCastableAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidValueException when the stored value isn't valid
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Abn
    {
        return $value === null ? null : self::parse($value);
    }

    /**
     * Accepts an Abn, or a string in any form Abn::from() reads, such as "51 824 753 556".
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
    private static function parse(mixed $value): Abn
    {
        return $value instanceof Abn ? $value : (self::tryParse($value) ?? throw InvalidValueException::abn());
    }

    /**
     * A string, or an int from an integer column, as an Abn. Null when it isn't valid.
     */
    private static function tryParse(mixed $value): ?Abn
    {
        return is_string($value) || is_int($value) ? Abn::tryFrom((string) $value) : null;
    }
}
