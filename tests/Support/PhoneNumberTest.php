<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\PhoneType;

mutates(PhoneNumber::class);

/*
 * Every number below is one ACMA reserves for fictional use, or is derived from one so that it
 * is invalid. Never replace them with real numbers.
 */

// Input, national digits, E.164, national format, international format.
dataset('mobile numbers', [
    'plain digits' => ['0491570006', '0491570006', '+61491570006', '0491 570 006', '+61 491 570 006'],
    'spaces' => ['0491 570 156', '0491570156', '+61491570156', '0491 570 156', '+61 491 570 156'],
    'hyphens' => ['0491-570-157', '0491570157', '+61491570157', '0491 570 157', '+61 491 570 157'],
    'dots' => ['0491.570.158', '0491570158', '+61491570158', '0491 570 158', '+61 491 570 158'],
    '+61' => ['+61 491 570 159', '0491570159', '+61491570159', '0491 570 159', '+61 491 570 159'],
    '61 without a plus sign' => ['61 491 570 110', '0491570110', '+61491570110', '0491 570 110', '+61 491 570 110'],
    '+61 with the (0) trunk' => ['+61 (0)491 570 156', '0491570156', '+61491570156', '0491 570 156', '+61 491 570 156'],
    '610 without a plus sign' => ['610491570157', '0491570157', '+61491570157', '0491 570 157', '+61 491 570 157'],
]);

// Input, national digits, area code, E.164, national format, international format.
dataset('landline numbers', [
    '(02) in parentheses' => ['(02) 5550 1234', '0255501234', '02', '+61255501234', '(02) 5550 1234', '+61 2 5550 1234'],
    '03 with non-breaking spaces (U+00A0)' => ["03\u{00A0}5550\u{00A0}1234", '0355501234', '03', '+61355501234', '(03) 5550 1234', '+61 3 5550 1234'],
    '07 as plain digits' => ['0755501234', '0755501234', '07', '+61755501234', '(07) 5550 1234', '+61 7 5550 1234'],
    '08 with hyphens' => ['08-5550-1234', '0855501234', '08', '+61855501234', '(08) 5550 1234', '+61 8 5550 1234'],
    'the (02) 7010 range' => ['(02) 7010 1234', '0270101234', '02', '+61270101234', '(02) 7010 1234', '+61 2 7010 1234'],
    'E.164' => ['+61355509876', '0355509876', '03', '+61355509876', '(03) 5550 9876', '+61 3 5550 9876'],
    '+61 with the (0) trunk' => ['+61 (0)7 5550 9876', '0755509876', '07', '+61755509876', '(07) 5550 9876', '+61 7 5550 9876'],
    '+61 with spaces' => ['+61 8 5550 9876', '0855509876', '08', '+61855509876', '(08) 5550 9876', '+61 8 5550 9876'],
]);

// Input, national digits, national format.
dataset('service numbers', [
    '1800 with spaces' => ['1800 160 401', '1800160401', '1800 160 401'],
    '1800 as plain digits' => ['1800975707', '1800975707', '1800 975 707'],
    '1300 with hyphens' => ['1300-975-707', '1300975707', '1300 975 707'],
    '1300 with non-breaking spaces (U+00A0)' => ["1300\u{00A0}975\u{00A0}707", '1300975707', '1300 975 707'],
    '13 with spaces' => ['13 12 34', '131234', '13 12 34'],
    '13 as plain digits' => ['131234', '131234', '13 12 34'],
]);

dataset('invalid phone numbers', [
    // International prefixes.
    'another country code' => '+44 491 570 156',
    '+61 followed by too few digits' => '+61 4915 7015',
    '+61 followed by too many digits' => '+61 491 570 1560',
    '+61 (0) followed by the wrong number of digits' => '+61 (0)491 570 1560',
    '+61 before a 1800 number' => '+61 1800 160 401',
    '+61 before a 13 number' => '+61 13 12 34',
    'a plus sign before the trunk zero' => '+0491 570 156',
    'a doubled plus sign' => '++61 491 570 156',
    'a plus sign inside the number' => '0491 570+156',

    // Prefixes outside the numbering plan.
    'an 00 prefix' => '0091 570 156',
    'an 05 prefix' => '0591 570 156',
    'a mobile without the trunk zero' => '491 570 156',
    'a landline without the trunk zero' => '2 5550 1234',
    'a landline without its area code' => '5550 1234',
    'a 10-digit number starting 1801' => '1801 160 401',
    'a 10-digit number starting 1301' => '1301 975 707',
    'a 6-digit number not starting 13' => '14 12 34',

    // Wrong lengths.
    'a mobile with 9 digits' => '0491 570 15',
    'a landline with 11 digits' => '(02) 5550 12345',
    '1800 followed by 5 digits' => '1800 160 40',
    '1800 followed by 7 digits' => '1800 160 4010',
    '13 followed by 3 digits' => '13 12 3',
    '13 followed by 5 digits' => '13 12 345',

    // Characters that are not stripped.
    'a letter in place of a digit' => '0491 570 15a',
    'a text prefix' => 'Mobile 0491 570 156',
    'an extension' => '(02) 5550 1234 ext 12',
    'slash separators' => '0491/570/156',
    'tab separators' => "0491\t570\t156",
    'a trailing newline' => "0491570156\n",
    'thin space separators (U+2009)' => "0491\u{2009}570\u{2009}156",
    'full-width digits' => '０４９１５７０１５６',
]);

dataset('blank phone numbers', [
    'an empty string' => '',
    'spaces only' => '   ',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
    'separators only' => ' (-.) ',
]);

it('parses a mobile number', function (string $input, string $value, string $e164, string $format, string $international): void {
    $phone = PhoneNumber::from($input);

    expect(PhoneNumber::isValid($input))->toBeTrue()
        ->and(PhoneNumber::tryFrom($input)?->value)->toBe($value)
        ->and($phone->value)->toBe($value)
        ->and($phone->type())->toBe(PhoneType::Mobile)
        ->and($phone->isMobile())->toBeTrue()
        ->and($phone->isLandline())->toBeFalse()
        ->and($phone->isService())->toBeFalse()
        ->and($phone->areaCode())->toBeNull()
        ->and($phone->toE164())->toBe($e164)
        ->and($phone->format())->toBe($format)
        ->and($phone->formatInternational())->toBe($international)
        ->and(PhoneNumber::from($e164)->equals($phone))->toBeTrue()
        ->and(PhoneNumber::from($format)->equals($phone))->toBeTrue()
        ->and(PhoneNumber::from($international)->equals($phone))->toBeTrue();
})->with('mobile numbers');

it('parses a landline number', function (string $input, string $value, string $areaCode, string $e164, string $format, string $international): void {
    $phone = PhoneNumber::from($input);

    expect(PhoneNumber::isValid($input))->toBeTrue()
        ->and(PhoneNumber::tryFrom($input)?->value)->toBe($value)
        ->and($phone->value)->toBe($value)
        ->and($phone->type())->toBe(PhoneType::Landline)
        ->and($phone->isMobile())->toBeFalse()
        ->and($phone->isLandline())->toBeTrue()
        ->and($phone->isService())->toBeFalse()
        ->and($phone->areaCode())->toBe($areaCode)
        ->and($phone->toE164())->toBe($e164)
        ->and($phone->format())->toBe($format)
        ->and($phone->formatInternational())->toBe($international)
        ->and(PhoneNumber::from($e164)->equals($phone))->toBeTrue()
        ->and(PhoneNumber::from($format)->equals($phone))->toBeTrue()
        ->and(PhoneNumber::from($international)->equals($phone))->toBeTrue();
})->with('landline numbers');

it('parses a service number, which cannot be dialled from overseas', function (string $input, string $value, string $format): void {
    $phone = PhoneNumber::from($input);

    expect(PhoneNumber::isValid($input))->toBeTrue()
        ->and(PhoneNumber::tryFrom($input)?->value)->toBe($value)
        ->and($phone->value)->toBe($value)
        ->and($phone->type())->toBe(PhoneType::Service)
        ->and($phone->isMobile())->toBeFalse()
        ->and($phone->isLandline())->toBeFalse()
        ->and($phone->isService())->toBeTrue()
        ->and($phone->areaCode())->toBeNull()
        ->and($phone->toE164())->toBeNull()
        ->and($phone->format())->toBe($format)
        ->and($phone->formatInternational())->toBeNull()
        ->and(PhoneNumber::from($format)->equals($phone))->toBeTrue();
})->with('service numbers');

it('rejects an invalid phone number without revealing it', function (string $input): void {
    expect(PhoneNumber::isValid($input))->toBeFalse()
        ->and(PhoneNumber::tryFrom($input))->toBeNull()
        ->and(fn () => PhoneNumber::from($input))->toThrow(function (InvalidValueException $exception) use ($input): void {
            expect($exception->getMessage())
                ->toBe('The given value is not a valid phone number.')
                ->not->toContain($input)
                ->not->toMatch('/\d/');
        });
})->with('invalid phone numbers');

it('rejects blank input', function (string $input): void {
    expect(PhoneNumber::isValid($input))->toBeFalse()
        ->and(PhoneNumber::tryFrom($input))->toBeNull()
        ->and(fn () => PhoneNumber::from($input))->toThrow(InvalidValueException::class, 'The given value is not a valid phone number.');
})->with('blank phone numbers');

it('treats null as invalid', function (): void {
    expect(PhoneNumber::isValid(null))->toBeFalse()
        ->and(PhoneNumber::tryFrom(null))->toBeNull();
});

it('equals the same number written differently, and no other', function (): void {
    $mobile = PhoneNumber::from('0491-570-156');

    expect($mobile->equals(PhoneNumber::from('+61 (0)491 570 156')))->toBeTrue()
        ->and(PhoneNumber::from('13 12 34')->equals(PhoneNumber::from('131234')))->toBeTrue()
        ->and($mobile->equals(PhoneNumber::from('0491 570 157')))->toBeFalse()
        ->and(PhoneNumber::from('(02) 5550 1234')->equals(PhoneNumber::from('(03) 5550 1234')))->toBeFalse()
        ->and(PhoneNumber::from('1800 975 707')->equals(PhoneNumber::from('1300 975 707')))->toBeFalse();
});

it('casts and JSON-encodes to its national digits', function (): void {
    $phone = PhoneNumber::from('+61 491 570 156');

    expect((string) $phone)->toBe('0491570156')
        ->and(json_encode(['phone' => $phone]))->toBe('{"phone":"0491570156"}');
});

it('can only be created through its named constructors', function (): void {
    expect(fn () => new PhoneNumber('0491570156'))->toThrow(Error::class, 'Call to private');
});
