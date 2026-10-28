<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Rules;

use Closure;
use EastpointLabs\LaravelAu\Support\Acn as AcnNumber;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an Australian Company Number (string rule: "acn").
 */
final class Acn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && (! is_string($value) || ! AcnNumber::isValid($value))) {
            $fail('au::validation.acn')->translate();
        }
    }
}
