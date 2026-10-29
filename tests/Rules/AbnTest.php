<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\Abn;
use EastpointLabs\LaravelAu\Support\Abn as AbnNumber;
use Illuminate\Support\Facades\Validator;

mutates(Abn::class);

describe('validation', function (): void {
    it('accepts a valid ABN', function (string $input): void {
        expect(Validator::make(['abn' => $input], ['abn' => [new Abn]])->passes())->toBeTrue();
    })->with([
        'Australian Taxation Office' => '51 824 753 556',
        'ABN Lookup documentation example, unspaced' => '53004085616',
        'hyphenated' => '83-914-571-673',
    ]);

    it('rejects an invalid ABN with the ABN message', function (string $input): void {
        $validator = Validator::make(['abn' => $input], ['abn' => [new Abn]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('abn'))->toBe('The abn must be a valid ABN.');
    })->with([
        'a bad checksum' => '51 824 753 557',
        'too short (10 digits)' => '5182475355',
        'a letter in place of a digit' => '51 824 753 55a',
        'an ACN rather than an ABN' => '004 085 616',
    ]);

    it('names the attribute in the message', function (): void {
        $validator = Validator::make(
            ['abn' => '51 824 753 557'],
            ['abn' => [new Abn]],
            [],
            ['abn' => 'supplier ABN'],
        );

        expect($validator->errors()->first('abn'))->toBe('The supplier ABN must be a valid ABN.');
    });

    it('gives the same result and message from the abn string rule', function (string $input): void {
        $object = Validator::make(['abn' => $input], ['abn' => [new Abn]]);
        $string = Validator::make(['abn' => $input], ['abn' => 'abn']);

        expect($string->passes())->toBe($object->passes())
            ->and($string->errors()->all())->toBe($object->errors()->all());
    })->with([
        'a valid ABN' => '51 824 753 556',
        'an invalid ABN' => '51 824 753 557',
    ]);
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (mixed $value): void {
        $validator = Validator::make(['abn' => $value], ['abn' => [new Abn]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('abn'))->toBe('The abn must be a valid ABN.');
    })->with([
        'an integer holding a valid ABN' => 51824753556,
        'a float holding a valid ABN' => 51824753556.0,
        'an array holding a valid ABN' => [['51 824 753 556']],
        'an empty array' => [[]],
        'an object' => new stdClass,
        'true' => true,
        'false' => false,
        'an ABN value object' => AbnNumber::from('51 824 753 556'),
    ]);
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (): void {
        expect(Validator::make(['abn' => null], ['abn' => [new Abn]])->passes())->toBeTrue()
            ->and(Validator::make(['abn' => null], ['abn' => ['nullable', new Abn]])->passes())->toBeTrue();
    });

    it('rejects a blank string, which the validator leaves to required', function (): void {
        $optional = Validator::make(['abn' => '   '], ['abn' => [new Abn]]);
        $required = Validator::make(['abn' => '   '], ['abn' => ['required', new Abn]]);

        expect(ruleFails(new Abn, ''))->toBeTrue()
            ->and(ruleFails(new Abn, '   '))->toBeTrue()
            ->and($optional->passes())->toBeTrue()
            ->and($required->fails())->toBeTrue()
            ->and($required->failed())->toBe(['abn' => ['Required' => []]]);
    });
});
