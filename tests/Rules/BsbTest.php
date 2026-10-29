<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\Bsb;
use EastpointLabs\LaravelAu\Support\Bsb as BsbNumber;
use Illuminate\Support\Facades\Validator;

mutates(Bsb::class);

describe('validation', function (): void {
    it('accepts a valid BSB', function (string $input): void {
        expect(Validator::make(['bsb' => $input], ['bsb' => [new Bsb]])->passes())->toBeTrue();
    })->with([
        'hyphenated' => '062-000',
        'digits only' => '062000',
        'any six digits, as there is no checksum' => '123 456',
    ]);

    it('rejects an invalid BSB with the BSB message', function (string $input): void {
        $validator = Validator::make(['bsb' => $input], ['bsb' => [new Bsb]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('bsb'))->toBe('The bsb must be a valid BSB.');
    })->with([
        'too short (5 digits)' => '06200',
        'too long (7 digits)' => '0620001',
        'a letter in place of a digit' => '062-00a',
        'a slash separator' => '062/000',
    ]);

    it('names the attribute in the message', function (): void {
        $validator = Validator::make(
            ['bsb' => '06200'],
            ['bsb' => [new Bsb]],
            [],
            ['bsb' => 'branch BSB'],
        );

        expect($validator->errors()->first('bsb'))->toBe('The branch BSB must be a valid BSB.');
    });

    it('gives the same result and message from the bsb string rule', function (string $input): void {
        $object = Validator::make(['bsb' => $input], ['bsb' => [new Bsb]]);
        $string = Validator::make(['bsb' => $input], ['bsb' => 'bsb']);

        expect($string->passes())->toBe($object->passes())
            ->and($string->errors()->all())->toBe($object->errors()->all());
    })->with([
        'a valid BSB' => '062-000',
        'an invalid BSB' => '06200',
    ]);
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (mixed $value): void {
        $validator = Validator::make(['bsb' => $value], ['bsb' => [new Bsb]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('bsb'))->toBe('The bsb must be a valid BSB.');
    })->with([
        'an integer of six digits' => 123456,
        'a float of six digits' => 123456.0,
        'an array holding a valid BSB' => [['062-000']],
        'an empty array' => [[]],
        'an object' => new stdClass,
        'true' => true,
        'false' => false,
        'a BSB value object' => BsbNumber::from('062-000'),
    ]);
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (): void {
        expect(Validator::make(['bsb' => null], ['bsb' => [new Bsb]])->passes())->toBeTrue()
            ->and(Validator::make(['bsb' => null], ['bsb' => ['nullable', new Bsb]])->passes())->toBeTrue();
    });

    it('rejects a blank string, which the validator leaves to required', function (): void {
        $optional = Validator::make(['bsb' => '   '], ['bsb' => [new Bsb]]);
        $required = Validator::make(['bsb' => '   '], ['bsb' => ['required', new Bsb]]);

        expect(ruleFails(new Bsb, ''))->toBeTrue()
            ->and(ruleFails(new Bsb, '   '))->toBeTrue()
            ->and($optional->passes())->toBeTrue()
            ->and($required->fails())->toBeTrue()
            ->and($required->failed())->toBe(['bsb' => ['Required' => []]]);
    });
});
