<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Rules;

use Closure;
use EastpointLabs\LaravelAu\Support\Bsb as BsbNumber;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the format of a BSB (string rule: "bsb"). It can't tell whether the branch exists.
 */
final class Bsb implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && (! is_string($value) || ! BsbNumber::isValid($value))) {
            $fail('au::validation.bsb')->translate();
        }
    }
}
