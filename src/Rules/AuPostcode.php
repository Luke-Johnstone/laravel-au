<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Rules;

use Closure;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Validates an Australian postcode, optionally requiring it to be in a given state (string
 * rule: "au_postcode", or "au_postcode:NSW").
 */
final class AuPostcode implements DataAwareRule, ValidationRule
{
    private ?State $state;

    private ?string $stateField = null;

    /** @var array<array-key, mixed> */
    private array $data = [];

    /**
     * Pass a state to require postcodes in that state. A string is matched loosely ("NSW",
     * "new south wales"), and an unrecognised one throws, so only pass states from your own
     * code. To check against a state the user chose, use matchingStateField().
     *
     * @throws InvalidArgumentException
     */
    public function __construct(State|string|null $state = null)
    {
        $this->state = is_string($state) ? self::resolveState($state) : $state;
    }

    /**
     * Require the postcode to be in the state held by another field. On a nested attribute, a
     * field without a dot is read as a sibling ("addresses.0.state"); a dotted field is an
     * absolute path. An empty or unrecognised state is ignored, so give the state field its
     * own rule, such as Rule::enum(State::class).
     */
    public static function matchingStateField(string $field): self
    {
        $rule = new self;
        $rule->stateField = $field;

        return $rule;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $postcode = is_string($value) ? Postcode::tryFrom($value) : null;

        if ($postcode === null) {
            $fail('au::validation.au_postcode')->translate();

            return;
        }

        $state = $this->state ?? $this->stateFromData($attribute);

        if ($state !== null && ! $postcode->belongsTo($state)) {
            $fail('au::validation.au_postcode_state')->translate(['state' => $state->label()]);
        }
    }

    private static function resolveState(string $state): State
    {
        return State::fromLoose($state)
            ?? throw new InvalidArgumentException('The given state is not an Australian state or territory.');
    }

    private function stateFromData(string $attribute): ?State
    {
        if ($this->stateField === null) {
            return null;
        }

        $path = str_contains($this->stateField, '.') || ! str_contains($attribute, '.')
            ? $this->stateField
            : Str::beforeLast($attribute, '.').'.'.$this->stateField;

        $state = Arr::get($this->data, $path);

        return match (true) {
            $state instanceof State => $state,
            is_string($state) => State::fromLoose($state),
            default => null,
        };
    }
}
