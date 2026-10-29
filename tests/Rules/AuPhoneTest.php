<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\AuPhone;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\PhoneType;
use Illuminate\Support\Facades\Validator;

mutates(AuPhone::class);

/*
 * Every number below is one ACMA reserves for fictional use, or is derived from one so that it
 * is invalid. Never replace them with real numbers.
 */

describe('allowed types', function (): void {
    it('passes only the types it allows', function (AuPhone|string $rule, array $allowed): void {
        $numbers = [
            'mobile' => '0491 570 156',
            'landline' => '(02) 5550 1234',
            '1800 number' => '1800 160 401',
            '13 number' => '13 12 34',
        ];

        $passing = array_filter(
            $numbers,
            fn (string $number): bool => Validator::make(['phone' => $number], ['phone' => [$rule]])->passes(),
        );

        expect(array_keys($passing))->toBe($allowed);
    })->with([
        'the default rule' => [fn (): AuPhone => new AuPhone, ['mobile', 'landline']],
        'mobile()' => [fn (): AuPhone => AuPhone::mobile(), ['mobile']],
        'landline()' => [fn (): AuPhone => AuPhone::landline(), ['landline']],
        'any()' => [fn (): AuPhone => AuPhone::any(), ['mobile', 'landline', '1800 number', '13 number']],
        'the constructor given Mobile and Service' => [
            fn (): AuPhone => new AuPhone(PhoneType::Mobile, PhoneType::Service),
            ['mobile', '1800 number', '13 number'],
        ],
        'au_phone' => ['au_phone', ['mobile', 'landline']],
        'au_phone:mobile' => ['au_phone:mobile', ['mobile']],
        'au_phone:landline' => ['au_phone:landline', ['landline']],
        'au_phone:service' => ['au_phone:service', ['1800 number', '13 number']],
        'au_phone:mobile,service' => ['au_phone:mobile,service', ['mobile', '1800 number', '13 number']],
        'au_phone:mobile,landline,service' => [
            'au_phone:mobile,landline,service',
            ['mobile', 'landline', '1800 number', '13 number'],
        ],
    ]);

    it('rejects a number of a type it does not allow with the phone message', function (): void {
        $validator = Validator::make(['phone' => '1800 160 401'], ['phone' => [new AuPhone]]);

        expect($validator->errors()->first('phone'))->toBe('The phone must be a valid Australian phone number.');
    });
});

describe('validation', function (): void {
    it('accepts a number in +61 form by default', function (string $input): void {
        expect(Validator::make(['phone' => $input], ['phone' => [new AuPhone]])->passes())->toBeTrue();
    })->with([
        'a mobile' => '+61 491 570 157',
        'a landline' => '+61 2 5550 1234',
    ]);

    it('rejects an invalid number even when every type is allowed', function (string $input): void {
        $validator = Validator::make(['phone' => $input], ['phone' => [AuPhone::any()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('phone'))->toBe('The phone must be a valid Australian phone number.');
    })->with([
        'another country code' => '+64 491 570 156',
        'a mobile with 9 digits' => '0491 570 15',
        'a letter in place of a digit' => '0491 570 15a',
        'an unallocated 05 prefix' => '0591 570 156',
    ]);

    it('names the attribute in the message', function (): void {
        $validator = Validator::make(
            ['phone' => '0491 570 15a'],
            ['phone' => [new AuPhone]],
            [],
            ['phone' => 'contact number'],
        );

        expect($validator->errors()->first('phone'))
            ->toBe('The contact number must be a valid Australian phone number.');
    });

    it('gives the same result and message from the au_phone string rule', function (string $input): void {
        $object = Validator::make(['phone' => $input], ['phone' => [new AuPhone]]);
        $string = Validator::make(['phone' => $input], ['phone' => 'au_phone']);

        expect($string->passes())->toBe($object->passes())
            ->and($string->errors()->all())->toBe($object->errors()->all());
    })->with([
        'a valid mobile' => '0491 570 156',
        'an invalid number' => '0491 570 15a',
    ]);
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (mixed $value): void {
        $validator = Validator::make(['phone' => $value], ['phone' => [AuPhone::any()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('phone'))->toBe('The phone must be a valid Australian phone number.');
    })->with([
        'an integer' => 491570156,
        'an array holding a valid number' => [['0491 570 156']],
        'an empty array' => [[]],
        'an object' => new stdClass,
        'true' => true,
        'false' => false,
        'a phone number value object' => PhoneNumber::from('0491 570 156'),
    ]);
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (): void {
        expect(Validator::make(['phone' => null], ['phone' => [new AuPhone]])->passes())->toBeTrue()
            ->and(Validator::make(['phone' => null], ['phone' => ['nullable', new AuPhone]])->passes())->toBeTrue();
    });

    it('rejects a blank string, which the validator leaves to required', function (): void {
        $optional = Validator::make(['phone' => '   '], ['phone' => [new AuPhone]]);
        $required = Validator::make(['phone' => '   '], ['phone' => ['required', new AuPhone]]);

        expect(ruleFails(AuPhone::any(), ''))->toBeTrue()
            ->and(ruleFails(AuPhone::any(), '   '))->toBeTrue()
            ->and($optional->passes())->toBeTrue()
            ->and($required->fails())->toBeTrue()
            ->and($required->failed())->toBe(['phone' => ['Required' => []]]);
    });
});
