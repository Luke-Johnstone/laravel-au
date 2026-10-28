<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Rules;

use Closure;
use EastpointLabs\LaravelAu\Support\Abn as AbnNumber;
use EastpointLabs\LaravelAu\Support\Acn as AcnNumber;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a field that takes either an ABN or an ACN (string rule: "abn_or_acn"). Eleven
 * digits are checked as an ABN and nine as an ACN.
 */
final class AbnOrAcn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! is_string($value) || ! (AbnNumber::isValid($value) || AcnNumber::isValid($value))) {
            $fail('au::validation.abn_or_acn')->translate();
        }
    }
}
