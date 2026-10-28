<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\AbnOrAcn;
use EastpointLabs\LaravelAu\Support\Abn as AbnNumber;
use EastpointLabs\LaravelAu\Support\Acn as AcnNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

mutates(AbnOrAcn::class);

// Both forms must behave the same, so most tests run once with each.
dataset('abn or acn rule forms', [
    'rule object' => [fn (): AbnOrAcn => new AbnOrAcn],
    'string rule' => [fn (): string => 'abn_or_acn'],
]);

dataset('valid abns and acns', [
    'ABN: Australian Taxation Office' => '51 824 753 556',
    'ABN: ABN Lookup documentation example' => '53 004 085 616',
    'ABN: checksum-valid example' => '83 914 571 673',
    'ABN: hyphens' => '51-824-753-556',
    'ABN: non-breaking spaces (U+00A0)' => "53\u{00A0}004\u{00A0}085\u{00A0}616",
    'ACN: ASIC example 000 000 019' => '000 000 019',
    'ACN: ASIC example 000 250 000' => '000 250 000',
    'ACN: ASIC example 000 500 005' => '000 500 005',
    'ACN: ASIC example 000 750 005' => '000 750 005',
    'ACN: ASIC example 001 000 004' => '001 000 004',
    'ACN: ASIC example 001 250 004' => '001 250 004',
    'ACN: ASIC example 001 500 009' => '001 500 009',
    'ACN: ASIC example 001 749 999' => '001 749 999',
    'ACN: ASIC example 001 999 999' => '001 999 999',
    'ACN: ASIC example 002 249 998' => '002 249 998',
    'ACN: ASIC example 010 749 961' => '010 749 961',
    'ACN: within the ABN Lookup documentation example' => '004 085 616',
    'ACN: canonical digits' => '004085616',
    'ACN: non-breaking spaces (U+00A0)' => "004\u{00A0}085\u{00A0}616",
]);

dataset('invalid abns and acns', [
    'ABN with a bad checksum' => '51 824 753 557',
    'ABN checksum failure with a zero body' => '11 000 000 000',
    'ABN with a letter in place of a digit' => '51 824 753 55a',
    'ACN with a bad check digit' => '000 000 018',
    'ACN of sequential digits' => '123 456 789',
    'ACN with a letter in place of a digit' => '000 000 01a',
    '10 digits, neither an ABN nor an ACN' => '5182475355',
    '10 digits, an ACN with an extra digit' => '0040856160',
    '8 digits' => '00408561',
    '12 digits' => '518247535560',
    'a letter prefix' => 'ABN 51 824 753 556',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
]);

dataset('non-string abn or acn values', [
    'an integer holding a valid ABN' => 51824753556,
    'an integer' => 4085616,
    'a float' => 51824753556.0,
    'an array holding a valid ABN' => [['51 824 753 556']],
    'an empty array' => [[]],
    'an object' => new stdClass,
    'an ABN value object' => AbnNumber::from('51 824 753 556'),
    'an ACN value object' => AcnNumber::from('004 085 616'),
    'true' => true,
    'false' => false,
]);

dataset('blank abn or acn strings', [
    'an empty string' => '',
    'spaces only' => '   ',
    'a tab and newline only' => "\t\n",
]);

it('is a validation rule', function (): void {
    expect(new AbnOrAcn)->toBeInstanceOf(ValidationRule::class);
});

describe('validation', function (): void {
    it('accepts a valid ABN or a valid ACN', function (Closure $rule, string $input): void {
        $validator = Validator::make(['business_number' => $input], ['business_number' => [$rule()]]);

        expect(AbnNumber::isValid($input) || AcnNumber::isValid($input))->toBeTrue()
            ->and($validator->passes())->toBeTrue();
    })->with('abn or acn rule forms')->with('valid abns and acns');

    it('rejects anything that is neither with the ABN or ACN message', function (Closure $rule, string $input): void {
        $validator = Validator::make(['business_number' => $input], ['business_number' => [$rule()]]);

        expect(AbnNumber::isValid($input))->toBeFalse()
            ->and(AcnNumber::isValid($input))->toBeFalse()
            ->and($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('business_number'))
            ->toBe('The business number must be a valid ABN or ACN.');
    })->with('abn or acn rule forms')->with('invalid abns and acns');

    it('names the attribute in the message', function (Closure $rule): void {
        $validator = Validator::make(
            ['business_number' => '5182475355'],
            ['business_number' => [$rule()]],
            [],
            ['business_number' => 'supplier ABN or ACN'],
        );

        expect($validator->errors()->first('business_number'))
            ->toBe('The supplier ABN or ACN must be a valid ABN or ACN.');
    })->with('abn or acn rule forms');
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (Closure $rule, mixed $value): void {
        $validator = Validator::make(['business_number' => $value], ['business_number' => [$rule()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('business_number'))
            ->toBe('The business number must be a valid ABN or ACN.');
    })->with('abn or acn rule forms')->with('non-string abn or acn values');
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (Closure $rule): void {
        expect(Validator::make(['business_number' => null], ['business_number' => [$rule()]])->passes())->toBeTrue();
    })->with('abn or acn rule forms');

    it('passes null alongside nullable', function (Closure $rule): void {
        expect(Validator::make(['business_number' => null], ['business_number' => ['nullable', $rule()]])->passes())->toBeTrue();
    })->with('abn or acn rule forms');

    it('leaves an empty or whitespace-only string to required', function (Closure $rule, string $input): void {
        expect(Validator::make(['business_number' => $input], ['business_number' => [$rule()]])->passes())->toBeTrue();
    })->with('abn or acn rule forms')->with('blank abn or acn strings');

    it('rejects a blank string when run on its own', function (string $input): void {
        expect(ruleFails(new AbnOrAcn, $input))->toBeTrue();
    })->with('blank abn or acn strings');

    it('passes null when run on its own', function (): void {
        expect(ruleFails(new AbnOrAcn, null))->toBeFalse();
    });
});
