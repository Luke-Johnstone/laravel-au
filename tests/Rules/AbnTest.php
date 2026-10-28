<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\Abn;
use EastpointLabs\LaravelAu\Support\Abn as AbnNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

mutates(Abn::class);

// Both forms must behave the same, so most tests run once with each.
dataset('abn rule forms', [
    'rule object' => [fn (): Abn => new Abn],
    'string rule' => [fn (): string => 'abn'],
]);

dataset('valid abns', [
    'Australian Taxation Office' => '51 824 753 556',
    'ABN Lookup documentation example' => '53 004 085 616',
    'checksum-valid example' => '83 914 571 673',
    'canonical digits' => '51824753556',
    'hyphens' => '51-824-753-556',
    'dots' => '53.004.085.616',
    'non-breaking spaces (U+00A0)' => "83\u{00A0}914\u{00A0}571\u{00A0}673",
]);

dataset('invalid abns', [
    'bad checksum' => '51 824 753 557',
    'checksum failure with a zero body' => '11 000 000 000',
    'too short (10 digits)' => '5182475355',
    'too long (12 digits)' => '518247535560',
    'a letter in place of a digit' => '51 824 753 55a',
    'a letter prefix' => 'ABN 51 824 753 556',
    'an ACN rather than an ABN' => '004 085 616',
    'slash separators' => '51/824/753/556',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
]);

dataset('non-string abn values', [
    'an integer holding a valid ABN' => 51824753556,
    'a float' => 51824753556.0,
    'an array holding a valid ABN' => [['51 824 753 556']],
    'an empty array' => [[]],
    'an object' => new stdClass,
    'an ABN value object' => AbnNumber::from('51 824 753 556'),
    'true' => true,
    'false' => false,
]);

dataset('blank abn strings', [
    'an empty string' => '',
    'spaces only' => '   ',
    'a tab and newline only' => "\t\n",
]);

it('is a validation rule', function (): void {
    expect(new Abn)->toBeInstanceOf(ValidationRule::class);
});

describe('validation', function (): void {
    it('accepts a valid ABN', function (Closure $rule, string $input): void {
        $validator = Validator::make(['abn' => $input], ['abn' => [$rule()]]);

        expect(AbnNumber::isValid($input))->toBeTrue()
            ->and($validator->passes())->toBeTrue();
    })->with('abn rule forms')->with('valid abns');

    it('rejects an invalid ABN with the ABN message', function (Closure $rule, string $input): void {
        $validator = Validator::make(['abn' => $input], ['abn' => [$rule()]]);

        expect(AbnNumber::isValid($input))->toBeFalse()
            ->and($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('abn'))->toBe('The abn must be a valid ABN.');
    })->with('abn rule forms')->with('invalid abns');

    it('names the attribute in the message', function (Closure $rule): void {
        $validator = Validator::make(
            ['abn' => '51 824 753 557'],
            ['abn' => [$rule()]],
            [],
            ['abn' => 'supplier ABN'],
        );

        expect($validator->errors()->first('abn'))->toBe('The supplier ABN must be a valid ABN.');
    })->with('abn rule forms');
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (Closure $rule, mixed $value): void {
        $validator = Validator::make(['abn' => $value], ['abn' => [$rule()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('abn'))->toBe('The abn must be a valid ABN.');
    })->with('abn rule forms')->with('non-string abn values');
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (Closure $rule): void {
        expect(Validator::make(['abn' => null], ['abn' => [$rule()]])->passes())->toBeTrue();
    })->with('abn rule forms');

    it('passes null alongside nullable', function (Closure $rule): void {
        expect(Validator::make(['abn' => null], ['abn' => ['nullable', $rule()]])->passes())->toBeTrue();
    })->with('abn rule forms');

    it('leaves an empty or whitespace-only string to required', function (Closure $rule, string $input): void {
        expect(Validator::make(['abn' => $input], ['abn' => [$rule()]])->passes())->toBeTrue();
    })->with('abn rule forms')->with('blank abn strings');

    it('rejects a blank string when run on its own', function (string $input): void {
        expect(AbnNumber::isValid($input))->toBeFalse()
            ->and(ruleFails(new Abn, $input))->toBeTrue();
    })->with('blank abn strings');

    it('passes null when run on its own', function (): void {
        expect(ruleFails(new Abn, null))->toBeFalse();
    });
});
