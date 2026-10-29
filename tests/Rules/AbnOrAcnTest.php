<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\AbnOrAcn;
use EastpointLabs\LaravelAu\Support\Abn as AbnNumber;
use EastpointLabs\LaravelAu\Support\Acn as AcnNumber;
use Illuminate\Support\Facades\Validator;

mutates(AbnOrAcn::class);

describe('validation', function (): void {
    it('accepts a valid ABN or a valid ACN', function (string $input): void {
        $validator = Validator::make(['business_number' => $input], ['business_number' => [new AbnOrAcn]]);

        expect($validator->passes())->toBeTrue();
    })->with([
        'ABN 51 824 753 556 (Australian Taxation Office)' => '51 824 753 556',
        'ABN 53 004 085 616 (ABN Lookup documentation example)' => '53 004 085 616',
        'ABN 83 914 571 673' => '83 914 571 673',
        'ACN 000 000 019' => '000 000 019',
        'ACN 000 250 000' => '000 250 000',
        'ACN 000 500 005' => '000 500 005',
        'ACN 000 750 005' => '000 750 005',
        'ACN 001 000 004' => '001 000 004',
        'ACN 001 250 004' => '001 250 004',
        'ACN 001 500 009' => '001 500 009',
        'ACN 001 749 999' => '001 749 999',
        'ACN 001 999 999' => '001 999 999',
        'ACN 002 249 998' => '002 249 998',
        'ACN 010 749 961' => '010 749 961',
        'ACN 004 085 616 (within the ABN Lookup documentation example)' => '004 085 616',
    ]);

    it('rejects anything that is neither with the ABN or ACN message', function (string $input): void {
        $validator = Validator::make(['business_number' => $input], ['business_number' => [new AbnOrAcn]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('business_number'))
            ->toBe('The business number must be a valid ABN or ACN.');
    })->with([
        'an ABN with a bad checksum' => '51 824 753 557',
        'an ABN of 11 000 000 000' => '11 000 000 000',
        'an ABN with a letter in place of a digit' => '51 824 753 55a',
        'an ACN with a bad check digit' => '000 000 018',
        'an ACN of sequential digits' => '123 456 789',
        '10 digits, neither an ABN nor an ACN' => '5182475355',
    ]);

    it('names the attribute in the message', function (): void {
        $validator = Validator::make(
            ['business_number' => '5182475355'],
            ['business_number' => [new AbnOrAcn]],
            [],
            ['business_number' => 'supplier ABN or ACN'],
        );

        expect($validator->errors()->first('business_number'))
            ->toBe('The supplier ABN or ACN must be a valid ABN or ACN.');
    });

    it('gives the same result and message from the abn_or_acn string rule', function (string $input): void {
        $object = Validator::make(['business_number' => $input], ['business_number' => [new AbnOrAcn]]);
        $string = Validator::make(['business_number' => $input], ['business_number' => 'abn_or_acn']);

        expect($string->passes())->toBe($object->passes())
            ->and($string->errors()->all())->toBe($object->errors()->all());
    })->with([
        'a valid ABN' => '51 824 753 556',
        'a valid ACN' => '000 000 019',
        '10 digits' => '5182475355',
    ]);
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (mixed $value): void {
        $validator = Validator::make(['business_number' => $value], ['business_number' => [new AbnOrAcn]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('business_number'))
            ->toBe('The business number must be a valid ABN or ACN.');
    })->with([
        'an integer holding a valid ABN' => 51824753556,
        'a float holding a valid ABN' => 51824753556.0,
        'an array holding a valid ABN' => [['51 824 753 556']],
        'an empty array' => [[]],
        'an object' => new stdClass,
        'true' => true,
        'false' => false,
        'an ABN value object' => AbnNumber::from('51 824 753 556'),
        'an ACN value object' => AcnNumber::from('004 085 616'),
    ]);
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (): void {
        expect(Validator::make(['business_number' => null], ['business_number' => [new AbnOrAcn]])->passes())->toBeTrue()
            ->and(Validator::make(['business_number' => null], ['business_number' => ['nullable', new AbnOrAcn]])->passes())->toBeTrue();
    });

    it('rejects a blank string, which the validator leaves to required', function (): void {
        $optional = Validator::make(['business_number' => '   '], ['business_number' => [new AbnOrAcn]]);
        $required = Validator::make(['business_number' => '   '], ['business_number' => ['required', new AbnOrAcn]]);

        expect(ruleFails(new AbnOrAcn, ''))->toBeTrue()
            ->and(ruleFails(new AbnOrAcn, '   '))->toBeTrue()
            ->and($optional->passes())->toBeTrue()
            ->and($required->fails())->toBeTrue()
            ->and($required->failed())->toBe(['business_number' => ['Required' => []]]);
    });
});
