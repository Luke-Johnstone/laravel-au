<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Rules;

use Closure;
use EastpointLabs\LaravelAu\Support\Abn as AbnNumber;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an Australian Business Number (string rule: "abn").
 */
final class Abn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && (! is_string($value) || ! AbnNumber::isValid($value))) {
            $fail('au::validation.abn')->translate();
        }
    }
}
