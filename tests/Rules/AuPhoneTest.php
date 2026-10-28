<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Rules\AuPhone;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\PhoneType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

mutates(AuPhone::class);

/*
 * Every number below is one ACMA reserves for fictional use, or is derived from one so that it
 * is invalid. Never replace them with real numbers.
 */

// The default rule in both forms: mobiles and landlines only.
dataset('default phone rule forms', [
    'rule object' => [fn (): AuPhone => new AuPhone],
    'string rule' => [fn (): string => 'au_phone'],
]);

// Rule object, the equivalent string rule, and the types it should allow.
dataset('phone rule variants', [
    'default' => [
        fn (): AuPhone => new AuPhone,
        'au_phone',
        [PhoneType::Mobile, PhoneType::Landline],
    ],
    'mobile()' => [
        fn (): AuPhone => AuPhone::mobile(),
        'au_phone:mobile',
        [PhoneType::Mobile],
    ],
    'landline()' => [
        fn (): AuPhone => AuPhone::landline(),
        'au_phone:landline',
        [PhoneType::Landline],
    ],
    'any()' => [
        fn (): AuPhone => AuPhone::any(),
        'au_phone:mobile,landline,service',
        [PhoneType::Mobile, PhoneType::Landline, PhoneType::Service],
    ],
    'service numbers only' => [
        fn (): AuPhone => new AuPhone(PhoneType::Service),
        'au_phone:service',
        [PhoneType::Service],
    ],
    'mobiles and service numbers' => [
        fn (): AuPhone => new AuPhone(PhoneType::Mobile, PhoneType::Service),
        'au_phone:mobile,service',
        [PhoneType::Mobile, PhoneType::Service],
    ],
    'all three types given to the constructor' => [
        fn (): AuPhone => new AuPhone(PhoneType::Mobile, PhoneType::Landline, PhoneType::Service),
        'au_phone:mobile,landline,service',
        [PhoneType::Mobile, PhoneType::Landline, PhoneType::Service],
    ],
]);

// A few numbers of each type, to check each variant against.
dataset('typed phone numbers', [
    'mobile' => ['0491 570 156', PhoneType::Mobile],
    'mobile in +61 form' => ['+61 491 570 157', PhoneType::Mobile],
    '(02) landline' => ['(02) 5550 1234', PhoneType::Landline],
    '(08) landline in +61 form' => ['+61 8 5550 1234', PhoneType::Landline],
    '1800 number' => ['1800 160 401', PhoneType::Service],
    '1300 number' => ['1300 975 707', PhoneType::Service],
    '13 number' => ['13 12 34', PhoneType::Service],
]);

// The rule in both forms with every type allowed.
dataset('all-types phone rule forms', [
    'any()' => [fn (): AuPhone => AuPhone::any()],
    'string rule with every type' => [fn (): string => 'au_phone:mobile,landline,service'],
]);

dataset('valid phone numbers', [
    'mobile 0491 570 006' => '0491 570 006',
    'mobile 0491 570 156' => '0491 570 156',
    'mobile 0491 570 157' => '0491 570 157',
    'mobile 0491 570 158' => '0491 570 158',
    'mobile 0491 570 159' => '0491 570 159',
    'mobile 0491 570 110' => '0491 570 110',
    'mobile with hyphens' => '0491-570-156',
    'mobile with non-breaking spaces (U+00A0)' => "0491\u{00A0}570\u{00A0}156",
    'mobile in E.164 form' => '+61491570156',
    'mobile with the (0) trunk' => '+61 (0)491 570 156',
    'landline (02) 5550' => '(02) 5550 1234',
    'landline (03) 5550' => '(03) 5550 1234',
    'landline (07) 5550' => '(07) 5550 1234',
    'landline (08) 5550' => '(08) 5550 1234',
    'landline (02) 7010' => '(02) 7010 1234',
    'landline in +61 form' => '+61 2 5550 1234',
    'service 1800 160 401' => '1800 160 401',
    'service 1800 975 707' => '1800 975 707',
    'service 1300 975 707' => '1300 975 707',
    'service 13 12 34' => '13 12 34',
]);

dataset('invalid phone numbers', [
    'another country code' => '+64 491 570 156',
    '+61 followed by too many digits' => '+61 491 570 1560',
    'a mobile with 9 digits' => '0491 570 15',
    'a mobile with 11 digits' => '0491 570 1560',
    'a landline with 9 digits' => '(02) 5550 123',
    'an 05 prefix' => '0591 570 156',
    'a 10-digit 18 number that is not 1800' => '1801 160 401',
    '13 followed by 3 digits' => '13 12 3',
    'a letter in place of a digit' => '0491 570 15a',
    'a text prefix' => 'Mobile 0491 570 156',
    'slash separators' => '0491/570/156',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
]);

dataset('non-string phone values', [
    'an integer' => 491570156,
    'a float' => 491570156.0,
    'an array holding a valid number' => [['0491 570 156']],
    'an empty array' => [[]],
    'an object' => new stdClass,
    'a phone number value object' => PhoneNumber::from('0491 570 156'),
    'true' => true,
    'false' => false,
]);

dataset('blank phone strings', [
    'an empty string' => '',
    'spaces only' => '   ',
    'a tab and newline only' => "\t\n",
]);

describe('construction', function (): void {
    it('is a validation rule however it is made', function (Closure $rule): void {
        expect($rule())->toBeInstanceOf(AuPhone::class)
            ->toBeInstanceOf(ValidationRule::class);
    })->with([
        'constructor with no types' => [fn (): AuPhone => new AuPhone],
        'constructor with types' => [fn (): AuPhone => new AuPhone(PhoneType::Mobile, PhoneType::Service)],
        'mobile()' => [fn (): AuPhone => AuPhone::mobile()],
        'landline()' => [fn (): AuPhone => AuPhone::landline()],
        'any()' => [fn (): AuPhone => AuPhone::any()],
    ]);
});

describe('allowed types', function (): void {
    it('allows mobiles and landlines but not service numbers by default', function (Closure $rule): void {
        $mobile = Validator::make(['phone' => '0491 570 156'], ['phone' => [$rule()]]);
        $landline = Validator::make(['phone' => '(02) 5550 1234'], ['phone' => [$rule()]]);
        $service = Validator::make(['phone' => '1800 160 401'], ['phone' => [$rule()]]);
        $thirteen = Validator::make(['phone' => '13 12 34'], ['phone' => [$rule()]]);

        expect($mobile->passes())->toBeTrue()
            ->and($landline->passes())->toBeTrue()
            ->and($service->fails())->toBeTrue()
            ->and($service->errors()->first('phone'))->toBe('The phone must be a valid Australian phone number.')
            ->and($thirteen->fails())->toBeTrue();
    })->with('default phone rule forms');

    it('passes a number only when its type is allowed', function (
        Closure $rule,
        string $stringRule,
        array $allowed,
        string $number,
        PhoneType $type,
    ): void {
        $allowedType = in_array($type, $allowed, true);
        $expectedMessage = $allowedType ? '' : 'The phone must be a valid Australian phone number.';

        $object = Validator::make(['phone' => $number], ['phone' => [$rule()]]);
        $string = Validator::make(['phone' => $number], ['phone' => [$stringRule]]);

        expect(PhoneNumber::from($number)->type())->toBe($type)
            ->and($object->passes())->toBe($allowedType)
            ->and($object->errors()->first('phone'))->toBe($expectedMessage)
            ->and($string->passes())->toBe($allowedType)
            ->and($string->errors()->first('phone'))->toBe($expectedMessage);
    })->with('phone rule variants')->with('typed phone numbers');
});

describe('validation', function (): void {
    it('accepts every valid number when all types are allowed', function (Closure $rule, string $input): void {
        $validator = Validator::make(['phone' => $input], ['phone' => [$rule()]]);

        expect(PhoneNumber::isValid($input))->toBeTrue()
            ->and($validator->passes())->toBeTrue();
    })->with('all-types phone rule forms')->with('valid phone numbers');

    it('rejects an invalid number with the phone message', function (Closure $rule, string $input): void {
        $validator = Validator::make(['phone' => $input], ['phone' => [$rule()]]);

        expect(PhoneNumber::isValid($input))->toBeFalse()
            ->and($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('phone'))->toBe('The phone must be a valid Australian phone number.');
    })->with('all-types phone rule forms')->with('invalid phone numbers');

    it('rejects an invalid number by default too', function (Closure $rule, string $input): void {
        $validator = Validator::make(['phone' => $input], ['phone' => [$rule()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('phone'))->toBe('The phone must be a valid Australian phone number.');
    })->with('default phone rule forms')->with('invalid phone numbers');

    it('names the attribute in the message', function (Closure $rule): void {
        $validator = Validator::make(
            ['phone' => '0491 570 15a'],
            ['phone' => [$rule()]],
            [],
            ['phone' => 'contact number'],
        );

        expect($validator->errors()->first('phone'))
            ->toBe('The contact number must be a valid Australian phone number.');
    })->with('default phone rule forms');
});

describe('non-string values', function (): void {
    it('rejects a value that is not a string', function (Closure $rule, mixed $value): void {
        $validator = Validator::make(['phone' => $value], ['phone' => [$rule()]]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('phone'))->toBe('The phone must be a valid Australian phone number.');
    })->with('all-types phone rule forms')->with('non-string phone values');
});

describe('null and blank input', function (): void {
    it('passes null, leaving it to required and nullable', function (Closure $rule): void {
        expect(Validator::make(['phone' => null], ['phone' => [$rule()]])->passes())->toBeTrue();
    })->with('default phone rule forms');

    it('passes null alongside nullable', function (Closure $rule): void {
        expect(Validator::make(['phone' => null], ['phone' => ['nullable', $rule()]])->passes())->toBeTrue();
    })->with('default phone rule forms');

    it('leaves an empty or whitespace-only string to required', function (Closure $rule, string $input): void {
        expect(Validator::make(['phone' => $input], ['phone' => [$rule()]])->passes())->toBeTrue();
    })->with('default phone rule forms')->with('blank phone strings');

    it('rejects a blank string when run on its own', function (string $input): void {
        expect(PhoneNumber::isValid($input))->toBeFalse()
            ->and(ruleFails(AuPhone::any(), $input))->toBeTrue();
    })->with('blank phone strings');

    it('passes null when run on its own', function (): void {
        expect(ruleFails(AuPhone::any(), null))->toBeFalse();
    });
});
