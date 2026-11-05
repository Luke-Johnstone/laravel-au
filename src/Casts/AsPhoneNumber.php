<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Casts;

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores an Australian phone number in E.164 (+61491570006) or as national digits
 * (0491570006), as set by phone.store_as in config/au.php, and reads either back as a
 * PhoneNumber.
 *
 * @implements CastsAttributes<PhoneNumber, PhoneNumber|string>
 */
final class AsPhoneNumber implements CastsAttributes, ComparesCastableAttributes, SerializesCastableAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidValueException when the stored value isn't a valid phone number
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhoneNumber
    {
        return $value === null ? null : self::parse($value);
    }

    /**
     * Accepts a PhoneNumber, or a string in any form PhoneNumber::from() reads, such as
     * "0491 570 006" or "+61 491 570 006".
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidValueException when the value isn't a valid phone number
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : self::storable(self::parse($value));
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
     * The form a phone number is stored in, per phone.store_as. The Filament phone field stores
     * numbers the same way.
     *
     * @internal
     */
    public static function storable(PhoneNumber $phone): string
    {
        if (config('au.phone.store_as') === 'national') {
            return $phone->value;
        }

        // Service numbers can't be dialled from overseas, so they have no E.164 form.
        return $phone->toE164() ?? $phone->value;
    }

    /**
     * @throws InvalidValueException
     */
    private static function parse(mixed $value): PhoneNumber
    {
        return $value instanceof PhoneNumber ? $value : (self::tryParse($value) ?? throw InvalidValueException::phone());
    }

    /**
     * A string, or an int from an integer column, as a PhoneNumber. Null when it isn't valid.
     */
    private static function tryParse(mixed $value): ?PhoneNumber
    {
        return is_string($value) || is_int($value) ? PhoneNumber::tryFrom((string) $value) : null;
    }
}
