<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Rules;

use Closure;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\PhoneType;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an Australian phone number of the allowed types (string rule: "au_phone", or
 * "au_phone:mobile,landline,service" to choose the types).
 */
final class AuPhone implements ValidationRule
{
    /** @var list<PhoneType> */
    private array $allow;

    /**
     * Allows mobiles and landlines unless you name the types.
     */
    public function __construct(PhoneType ...$allow)
    {
        $this->allow = $allow === [] ? [PhoneType::Mobile, PhoneType::Landline] : array_values($allow);
    }

    public static function mobile(): self
    {
        return new self(PhoneType::Mobile);
    }

    public static function landline(): self
    {
        return new self(PhoneType::Landline);
    }

    /**
     * Mobiles, landlines and 13/1300/1800 service numbers.
     */
    public static function any(): self
    {
        return new self(...PhoneType::cases());
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $phone = is_string($value) ? PhoneNumber::tryFrom($value) : null;

        if ($phone === null || ! in_array($phone->type(), $this->allow, true)) {
            $fail('au::validation.au_phone')->translate();
        }
    }
}
