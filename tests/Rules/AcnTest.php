<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\Acn;
use EastpointLabs\LaravelAu\Support\Acn as AcnNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

mutates(Acn::class);

// Both forms must behave the same, so most tests run once with each.
dataset('acn rule forms', [
    'rule object' => [fn (): Acn => new Acn],
    'string rule' => [fn (): string => 'acn'],
]);

dataset('valid acns', [
    'ASIC example 000 000 019' => '000 000 019',
    'ASIC example 000 250 000' => '000 250 000',
    'ASIC example 000 500 005' => '000 500 005',
    'ASIC example 000 750 005' => '000 750 005',
    'ASIC example 001 000 004' => '001 000 004',
    'ASIC example 001 250 004' => '001 250 004',
    'ASIC example 001 500 009' => '001 500 009',
    'ASIC example 001 749 999' => '001 749 999',
    'ASIC example 001 999 999' => '001 999 999',
    'ASIC example 002 249 998' => '002 249 998',
    'ASIC example 010 749 961' => '010 749 961',
    'ACN within the ABN Lookup documentation example' => '004 085 616',
    'canonical digits' => '004085616',
    'hyphens' => '000-250-000',
    'non-breaking spaces (U+00A0)' => "001\u{00A0}500\u{00A0}009",
]);

dataset('invalid acns', [
    'bad check digit' => '000 000 018',
    'sequential digits' => '123 456 789',
    'too short (8 digits)' => '00000019',
    'too long (10 digits)' => '0000000190',
    'an ABN rather than an ACN' => '53 004 085 616',
    'a letter in place of a digit' => '000 000 01a',
    'a letter prefix' => 'ACN 000 000 019',
    'slash separators' => '000/000/019',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
]);

dataset('non-string acn values', [
    'an integer' => 4085616,
    'a float' => 4085616.0,
    'an array holding a valid ACN' => [['004 085 616']],
    'an empty array' => [[]],
    'an object' => new stdClass,
    'an ACN value object' => AcnNumber::from('004 085 616'),
    'true' => true,
    'false' => false,
]);

dataset('blank acn strings', [
    'an empty string' => '',
    'spaces only' => '   ',
    'a tab and newline only' => "\t\n",
]);

it('is a validation rule', function (): void {
    expect(new Acn)->toBeInstanceOf(ValidationRule::class);
});

describe('validation', function (): void {
    it('accepts a valid ACN', function (Closure $rule, string $input): void {
        $validator = Validator::make(['acn' => $input], ['acn' => [$rule()]]);

        expect(AcnNumber::isValid($input))->toBeTrue()
            ->and($validator->passes())->toBeTrue();
    })->with('acn rule forms')->with('valid acns');

    it('rejects an invalid ACN with the ACN message', function (Closure $rule, string $input): void {
        $validator = Validator::make(['acn' => $input], ['acn' => [$rule()]]);

        expect(AcnNumber::isValid($input))->toBeFalse()
            ->and($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('acn'))->toBe('The acn must be a valid ACN.');
    })->with('acn rule forms')->with('invalid acns');

    it('names the attribute in the message', function (Closure $rule): void {
        $validator = Validator::make(
            ['acn' => '000 000 018'],
            ['acn' => [$rule()]],
            [],
            ['acn' => 'company ACN'],
        );

        expect($validator->errors()->first('acn'))->toBe('The company ACN must be a valid ACN.');
    })->with('acn rule forms');
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (Closure $rule, mixed $value): void {
        $validator = Validator::make(['acn' => $value], ['acn' => [$rule()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('acn'))->toBe('The acn must be a valid ACN.');
    })->with('acn rule forms')->with('non-string acn values');
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (Closure $rule): void {
        expect(Validator::make(['acn' => null], ['acn' => [$rule()]])->passes())->toBeTrue();
    })->with('acn rule forms');

    it('passes null alongside nullable', function (Closure $rule): void {
        expect(Validator::make(['acn' => null], ['acn' => ['nullable', $rule()]])->passes())->toBeTrue();
    })->with('acn rule forms');

    it('leaves an empty or whitespace-only string to required', function (Closure $rule, string $input): void {
        expect(Validator::make(['acn' => $input], ['acn' => [$rule()]])->passes())->toBeTrue();
    })->with('acn rule forms')->with('blank acn strings');

    it('rejects a blank string when run on its own', function (string $input): void {
        expect(AcnNumber::isValid($input))->toBeFalse()
            ->and(ruleFails(new Acn, $input))->toBeTrue();
    })->with('blank acn strings');

    it('passes null when run on its own', function (): void {
        expect(ruleFails(new Acn, null))->toBeFalse();
    });
});
