<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Rules;

use Closure;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;
use WeakMap;

/**
 * Backs a string rule such as "abn" or "au_phone:mobile" with its rule object, so both forms
 * pass and fail the same input with the same message.
 *
 * @internal
 */
final readonly class StringRule
{
    /**
     * The failure message from the last failed check on each validator. Laravel calls the
     * replacer straight after a failed extension, which takes the message back out.
     *
     * @var WeakMap<Validator, string>
     */
    private WeakMap $failures;

    /**
     * @param  Closure(list<string>): ValidationRule  $makeRule  builds the rule object from the string rule's parameters
     */
    public function __construct(
        private Translator $translator,
        private Closure $makeRule,
    ) {
        $this->failures = new WeakMap;
    }

    /**
     * Validator extension: run the rule object and remember its message if it fails.
     *
     * @param  list<string>  $parameters
     */
    public function passes(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        $failure = $this->failureMessage(($this->makeRule)($parameters), $attribute, $value);

        if ($failure === null) {
            return true;
        }

        $this->failures[$validator] = $failure;

        return false;
    }

    /**
     * Validator replacer: the rule object's message, unless the app has its own.
     *
     * Laravel has already looked for a custom message or an app "validation.{rule}" line. If it
     * found one, $message holds it and is kept; otherwise $message is the bare key.
     *
     * @param  list<string>  $parameters
     */
    public function message(string $message, string $attribute, string $rule, array $parameters, Validator $validator): string
    {
        $failure = $this->failures[$validator] ?? null;
        unset($this->failures[$validator]);

        if ($failure === null || $message !== "validation.{$rule}") {
            return $message;
        }

        // Replace :attribute the way Laravel does for rule objects. Passing a class name rather
        // than $rule keeps Laravel from calling this replacer again.
        return $validator->makeReplacements($failure, $attribute, self::class, []);
    }

    /**
     * Run the rule object and return its message, translated into the current locale, or null
     * when it passes.
     */
    private function failureMessage(ValidationRule $rule, string $attribute, mixed $value): ?string
    {
        $failure = null;

        $rule->validate(
            $attribute,
            $value,
            function (string $message) use (&$failure): PotentiallyTranslatedString {
                return $failure = new PotentiallyTranslatedString($message, $this->translator);
            },
        );

        return $failure?->toString();
    }
}
