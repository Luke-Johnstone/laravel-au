<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\Bsb;
use EastpointLabs\LaravelAu\Support\Bsb as BsbNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

mutates(Bsb::class);

// Both forms must behave the same, so most tests run once with each.
dataset('bsb rule forms', [
    'rule object' => [fn (): Bsb => new Bsb],
    'string rule' => [fn (): string => 'bsb'],
]);

dataset('valid bsbs', [
    'hyphenated' => '062-000',
    'a space' => '062 000',
    'a dot' => '062.000',
    'canonical digits' => '062000',
    'a non-breaking space (U+00A0)' => "062\u{00A0}000",
    'any six digits, as there is no checksum' => '123-456',
]);

dataset('invalid bsbs', [
    'too short (5 digits)' => '06200',
    'too short, hyphenated (5 digits)' => '062-00',
    'too long (7 digits)' => '0620001',
    'too long, hyphenated (7 digits)' => '062-0001',
    'a letter in place of a digit' => '062-00a',
    'a letter prefix' => 'BSB 062-000',
    'a slash separator' => '062/000',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
]);

dataset('non-string bsb values', [
    'an integer of six digits' => 123456,
    'a float' => 123456.0,
    'an array holding a valid BSB' => [['062-000']],
    'an empty array' => [[]],
    'an object' => new stdClass,
    'a BSB value object' => BsbNumber::from('062-000'),
    'true' => true,
    'false' => false,
]);

dataset('blank bsb strings', [
    'an empty string' => '',
    'spaces only' => '   ',
    'a tab and newline only' => "\t\n",
]);

it('is a validation rule', function (): void {
    expect(new Bsb)->toBeInstanceOf(ValidationRule::class);
});

describe('validation', function (): void {
    it('accepts a valid BSB', function (Closure $rule, string $input): void {
        $validator = Validator::make(['bsb' => $input], ['bsb' => [$rule()]]);

        expect(BsbNumber::isValid($input))->toBeTrue()
            ->and($validator->passes())->toBeTrue();
    })->with('bsb rule forms')->with('valid bsbs');

    it('rejects an invalid BSB with the BSB message', function (Closure $rule, string $input): void {
        $validator = Validator::make(['bsb' => $input], ['bsb' => [$rule()]]);

        expect(BsbNumber::isValid($input))->toBeFalse()
            ->and($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('bsb'))->toBe('The bsb must be a valid BSB.');
    })->with('bsb rule forms')->with('invalid bsbs');

    it('names the attribute in the message', function (Closure $rule): void {
        $validator = Validator::make(
            ['bsb' => '06200'],
            ['bsb' => [$rule()]],
            [],
            ['bsb' => 'branch BSB'],
        );

        expect($validator->errors()->first('bsb'))->toBe('The branch BSB must be a valid BSB.');
    })->with('bsb rule forms');
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (Closure $rule, mixed $value): void {
        $validator = Validator::make(['bsb' => $value], ['bsb' => [$rule()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('bsb'))->toBe('The bsb must be a valid BSB.');
    })->with('bsb rule forms')->with('non-string bsb values');
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (Closure $rule): void {
        expect(Validator::make(['bsb' => null], ['bsb' => [$rule()]])->passes())->toBeTrue();
    })->with('bsb rule forms');

    it('passes null alongside nullable', function (Closure $rule): void {
        expect(Validator::make(['bsb' => null], ['bsb' => ['nullable', $rule()]])->passes())->toBeTrue();
    })->with('bsb rule forms');

    it('leaves an empty or whitespace-only string to required', function (Closure $rule, string $input): void {
        expect(Validator::make(['bsb' => $input], ['bsb' => [$rule()]])->passes())->toBeTrue();
    })->with('bsb rule forms')->with('blank bsb strings');

    it('rejects a blank string when run on its own', function (string $input): void {
        expect(BsbNumber::isValid($input))->toBeFalse()
            ->and(ruleFails(new Bsb, $input))->toBeTrue();
    })->with('blank bsb strings');

    it('passes null when run on its own', function (): void {
        expect(ruleFails(new Bsb, null))->toBeFalse();
    });
});
