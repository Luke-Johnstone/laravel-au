<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\Acn;
use EastpointLabs\LaravelAu\Support\Acn as AcnNumber;
use Illuminate\Support\Facades\Validator;

mutates(Acn::class);

describe('validation', function (): void {
    it('accepts a valid ACN', function (string $input): void {
        expect(Validator::make(['acn' => $input], ['acn' => [new Acn]])->passes())->toBeTrue();
    })->with([
        'ASIC example 000 000 019' => '000 000 019',
        'the ACN within the ABN Lookup documentation example, unspaced' => '004085616',
        'hyphenated' => '010-749-961',
    ]);

    it('rejects an invalid ACN with the ACN message', function (string $input): void {
        $validator = Validator::make(['acn' => $input], ['acn' => [new Acn]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('acn'))->toBe('The acn must be a valid ACN.');
    })->with([
        'a bad check digit' => '000 000 018',
        'too short (8 digits)' => '00000019',
        'a letter in place of a digit' => '000 000 01a',
        'an ABN rather than an ACN' => '53 004 085 616',
    ]);

    it('names the attribute in the message', function (): void {
        $validator = Validator::make(
            ['acn' => '000 000 018'],
            ['acn' => [new Acn]],
            [],
            ['acn' => 'company ACN'],
        );

        expect($validator->errors()->first('acn'))->toBe('The company ACN must be a valid ACN.');
    });

    it('gives the same result and message from the acn string rule', function (string $input): void {
        $object = Validator::make(['acn' => $input], ['acn' => [new Acn]]);
        $string = Validator::make(['acn' => $input], ['acn' => 'acn']);

        expect($string->passes())->toBe($object->passes())
            ->and($string->errors()->all())->toBe($object->errors()->all());
    })->with([
        'a valid ACN' => '000 000 019',
        'an invalid ACN' => '000 000 018',
    ]);
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (mixed $value): void {
        $validator = Validator::make(['acn' => $value], ['acn' => [new Acn]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('acn'))->toBe('The acn must be a valid ACN.');
    })->with([
        'an integer' => 4085616,
        'an array holding a valid ACN' => [['004 085 616']],
        'an empty array' => [[]],
        'an object' => new stdClass,
        'true' => true,
        'false' => false,
        'an ACN value object' => AcnNumber::from('004 085 616'),
    ]);
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (): void {
        expect(Validator::make(['acn' => null], ['acn' => [new Acn]])->passes())->toBeTrue()
            ->and(Validator::make(['acn' => null], ['acn' => ['nullable', new Acn]])->passes())->toBeTrue();
    });

    it('rejects a blank string, which the validator leaves to required', function (): void {
        $optional = Validator::make(['acn' => '   '], ['acn' => [new Acn]]);
        $required = Validator::make(['acn' => '   '], ['acn' => ['required', new Acn]]);

        expect(ruleFails(new Acn, ''))->toBeTrue()
            ->and(ruleFails(new Acn, '   '))->toBeTrue()
            ->and($optional->passes())->toBeTrue()
            ->and($required->fails())->toBeTrue()
            ->and($required->failed())->toBe(['acn' => ['Required' => []]]);
    });
});
