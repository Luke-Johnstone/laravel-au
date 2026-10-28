<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\PhoneType;
use Pest\Expectation;

mutates(PhoneNumber::class);

/*
 * Apart from the overseas example, every number below is one ACMA reserves for fictional use,
 * or is derived from one so that it is invalid. Never replace them with real numbers.
 */

// Input, national digits, E.164, national format, international format.
dataset('mobile numbers', [
    'spaces' => ['0491 570 156', '0491570156', '+61491570156', '0491 570 156', '+61 491 570 156'],
    'hyphens' => ['0491-570-156', '0491570156', '+61491570156', '0491 570 156', '+61 491 570 156'],
    'parentheses around the prefix' => ['(0491) 570 156', '0491570156', '+61491570156', '0491 570 156', '+61 491 570 156'],
    '+61 with spaces' => ['+61 491 570 156', '0491570156', '+61491570156', '0491 570 156', '+61 491 570 156'],
    '61 without a plus sign' => ['61491570156', '0491570156', '+61491570156', '0491 570 156', '+61 491 570 156'],
    '+61 with the (0) trunk' => ['+61 (0)491 570 156', '0491570156', '+61491570156', '0491 570 156', '+61 491 570 156'],
    'grouped 2-4-4' => ['04 9157 0156', '0491570156', '+61491570156', '0491 570 156', '+61 491 570 156'],
    'national digits' => ['0491570006', '0491570006', '+61491570006', '0491 570 006', '+61 491 570 006'],
    'repeated separators' => ['0491  570--006', '0491570006', '+61491570006', '0491 570 006', '+61 491 570 006'],
    '610 without a plus sign' => ['610491570006', '0491570006', '+61491570006', '0491 570 006', '+61 491 570 006'],
    'dots' => ['0491.570.157', '0491570157', '+61491570157', '0491 570 157', '+61 491 570 157'],
    '+61 and (0) with a space between' => ['+61 (0) 491 570 157', '0491570157', '+61491570157', '0491 570 157', '+61 491 570 157'],
    'non-breaking spaces (U+00A0)' => ["0491\u{00A0}570\u{00A0}158", '0491570158', '+61491570158', '0491 570 158', '+61 491 570 158'],
    '+61 in parentheses' => ['(+61) 491 570 158', '0491570158', '+61491570158', '0491 570 158', '+61 491 570 158'],
    'E.164' => ['+61491570159', '0491570159', '+61491570159', '0491 570 159', '+61 491 570 159'],
    'spaces around +61' => ['  +61 491 570 159  ', '0491570159', '+61491570159', '0491 570 159', '+61 491 570 159'],
    '+61 with hyphens' => ['+61-491-570-110', '0491570110', '+61491570110', '0491 570 110', '+61 491 570 110'],
    'mixed separators' => ["+61\u{00A0}(0)491-570.110", '0491570110', '+61491570110', '0491 570 110', '+61 491 570 110'],
]);

// Input, national digits, area code, E.164, national format, international format.
dataset('landline numbers', [
    '(02) with parentheses' => ['(02) 5550 1234', '0255501234', '02', '+61255501234', '(02) 5550 1234', '+61 2 5550 1234'],
    '03 with spaces' => ['03 5550 1234', '0355501234', '03', '+61355501234', '(03) 5550 1234', '+61 3 5550 1234'],
    '+61 7 with spaces' => ['+61 7 5550 1234', '0755501234', '07', '+61755501234', '(07) 5550 1234', '+61 7 5550 1234'],
    '08 with spaces' => ['08 5550 1234', '0855501234', '08', '+61855501234', '(08) 5550 1234', '+61 8 5550 1234'],
    '+61 with the (0) trunk' => ['+61 (0)2 5550 1234', '0255501234', '02', '+61255501234', '(02) 5550 1234', '+61 2 5550 1234'],
    '(02) 7010 range' => ['(02) 7010 1234', '0270101234', '02', '+61270101234', '(02) 7010 1234', '+61 2 7010 1234'],
    'national digits' => ['0355509876', '0355509876', '03', '+61355509876', '(03) 5550 9876', '+61 3 5550 9876'],
    'hyphens' => ['07-5550-0123', '0755500123', '07', '+61755500123', '(07) 5550 0123', '+61 7 5550 0123'],
    'dots' => ['08.5550.4321', '0855504321', '08', '+61855504321', '(08) 5550 4321', '+61 8 5550 4321'],
    'non-breaking spaces (U+00A0)' => ["(02)\u{00A0}5550\u{00A0}9876", '0255509876', '02', '+61255509876', '(02) 5550 9876', '+61 2 5550 9876'],
    '61 without a plus sign' => ['61 3 5550 0123', '0355500123', '03', '+61355500123', '(03) 5550 0123', '+61 3 5550 0123'],
    '610 without a plus sign' => ['610755504321', '0755504321', '07', '+61755504321', '(07) 5550 4321', '+61 7 5550 4321'],
    'E.164' => ['+61270109876', '0270109876', '02', '+61270109876', '(02) 7010 9876', '+61 2 7010 9876'],
    'no space after the area code' => ['(08)55501234', '0855501234', '08', '+61855501234', '(08) 5550 1234', '+61 8 5550 1234'],
    '(0) trunk with hyphens' => ['+61 (0)3-5550-4321', '0355504321', '03', '+61355504321', '(03) 5550 4321', '+61 3 5550 4321'],
    'grouped like a mobile' => ['0755 501 234', '0755501234', '07', '+61755501234', '(07) 5550 1234', '+61 7 5550 1234'],
]);

// Input, national digits, national format.
dataset('service numbers', [
    '1800 with spaces' => ['1800 160 401', '1800160401', '1800 160 401'],
    '1800 with dots' => ['1800.160.401', '1800160401', '1800 160 401'],
    '1800 as national digits' => ['1800975707', '1800975707', '1800 975 707'],
    '1800 with non-breaking spaces (U+00A0)' => ["1800\u{00A0}975\u{00A0}707", '1800975707', '1800 975 707'],
    '1300 with spaces' => ['1300 975 707', '1300975707', '1300 975 707'],
    '1300 with hyphens' => ['1300-975-707', '1300975707', '1300 975 707'],
    '1300 as national digits' => ['1300975707', '1300975707', '1300 975 707'],
    '1300 grouped 4-2-2-2' => ['1300 97 57 07', '1300975707', '1300 975 707'],
    '13 with spaces' => ['13 12 34', '131234', '13 12 34'],
    '13 as national digits' => ['131234', '131234', '13 12 34'],
    '13 grouped 2-4' => ['13 1234', '131234', '13 12 34'],
    '13 with hyphens' => ['13-12-34', '131234', '13 12 34'],
    '13 with non-breaking spaces (U+00A0)' => ["13\u{00A0}12\u{00A0}34", '131234', '13 12 34'],
]);

dataset('invalid phone numbers', [
    // International prefixes.
    'another country code' => '+64 21 123 4567',
    'another country code without a plus sign' => '64 21 123 4567',
    '+61 followed by too few digits' => '+61 4915 7015',
    '+61 followed by too many digits' => '+61 491 570 1560',
    '61 followed by too few digits' => '61 4915 7015',
    '+61 (0) followed by too few digits' => '+61 (0)491 570 15',
    '+61 (0) followed by too many digits' => '+61 (0)491 570 1560',
    '+61 before a 1800 number' => '+61 1800 160 401',
    '+61 before a 13 number' => '+61 13 12 34',
    '+61 before an 05 prefix' => '+61 591 570 156',
    'the country code alone' => '+61',
    'a plus sign alone' => '+',
    'a plus sign before the trunk zero' => '+0491 570 156',
    'a doubled plus sign' => '++61 491 570 156',
    'a plus sign inside the number' => '0491 570+156',

    // Prefixes outside the numbering plan.
    'an 00 prefix' => '0091 570 156',
    'an 01 prefix' => '0191 570 156',
    'an 05 prefix' => '0591 570 156',
    'an 06 prefix' => '0691 570 156',
    'an 09 prefix' => '0991 570 156',
    'a mobile without the trunk zero' => '491 570 156',
    'a landline without the trunk zero' => '2 5550 1234',
    'a 10-digit 18 number that is not 1800' => '1801 160 401',
    'a 10-digit 13 number that is not 1300' => '1301 975 707',
    'another 10-digit 13 number that is not 1300' => '1310 975 707',
    'a 6-digit number that does not start with 13' => '14 12 34',

    // Wrong lengths.
    'a mobile with 9 digits' => '0491 570 15',
    'a mobile with 11 digits' => '0491 570 1560',
    'a landline with 9 digits' => '(02) 5550 123',
    'a landline with 11 digits' => '(02) 5550 12345',
    'a landline without its area code' => '5550 1234',
    '1800 followed by 5 digits' => '1800 160 40',
    '1800 followed by 7 digits' => '1800 160 4010',
    '1300 followed by 5 digits' => '1300 975 70',
    '1300 followed by 7 digits' => '1300 975 7070',
    '13 followed by 3 digits' => '13 12 3',
    '13 followed by 5 digits' => '13 12 345',

    // Letters and characters that are not stripped.
    'a letter in place of a digit' => '0491 570 15a',
    'a letter O in place of the trunk zero' => 'O491 570 156',
    'a text prefix' => 'Mobile 0491 570 156',
    'an extension' => '(02) 5550 1234 ext 12',
    'tab separators' => "0491\t570\t156",
    'a leading tab' => "\t0491570156",
    'newline separators' => "0491\n570\n156",
    'a trailing newline' => "0491570156\n",
    'a trailing newline on a 13 number' => "13 12 34\n",
    'slash separators' => '0491/570/156',
    'underscore separators' => '0491_570_156',
    'square brackets around the area code' => '[02] 5550 1234',
    'thin space separators (U+2009)' => "0491\u{2009}570\u{2009}156",
    'narrow no-break space separators (U+202F)' => "0491\u{202F}570\u{202F}156",
    'full-width digits' => '０４９１５７０１５６',
]);

dataset('blank phone inputs', [
    'an empty string' => '',
    'spaces only' => '   ',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
    'separators only' => ' (-.) ',
    'a tab and newline only' => "\t\n",
]);

describe('mobile numbers', function (): void {
    it('accepts each typed form', function (string $input, string $value): void {
        expect(PhoneNumber::isValid($input))->toBeTrue()
            ->and(PhoneNumber::tryFrom($input)?->value)->toBe($value);
    })->with('mobile numbers');

    it('parses to the national digits and formats for E.164, national and international display', function (string $input, string $value, string $e164, string $format, string $international): void {
        $phone = PhoneNumber::from($input);

        expect($phone->value)->toBe($value)
            ->and($phone->toE164())->toBe($e164)
            ->and($phone->format())->toBe($format)
            ->and($phone->formatInternational())->toBe($international);
    })->with('mobile numbers');

    it('is a mobile according to type() and every is*() method', function (string $input): void {
        $phone = PhoneNumber::from($input);

        expect($phone->type())->toBe(PhoneType::Mobile)
            ->and($phone->isMobile())->toBeTrue()
            ->and($phone->isLandline())->toBeFalse()
            ->and($phone->isService())->toBeFalse();
    })->with('mobile numbers');

    it('has no area code', function (string $input): void {
        expect(PhoneNumber::from($input)->areaCode())->toBeNull();
    })->with('mobile numbers');

    it('round-trips through E.164 and both display formats', function (string $input): void {
        $phone = PhoneNumber::from($input);
        $e164 = $phone->toE164();
        $international = $phone->formatInternational();

        expect($e164)->not->toBeNull()
            ->and($international)->not->toBeNull()
            ->and(PhoneNumber::from((string) $e164)->equals($phone))->toBeTrue()
            ->and(PhoneNumber::from($phone->format())->equals($phone))->toBeTrue()
            ->and(PhoneNumber::from((string) $international)->equals($phone))->toBeTrue();
    })->with('mobile numbers');
});

describe('landline numbers', function (): void {
    it('accepts each typed form', function (string $input, string $value): void {
        expect(PhoneNumber::isValid($input))->toBeTrue()
            ->and(PhoneNumber::tryFrom($input)?->value)->toBe($value);
    })->with('landline numbers');

    it('parses to the national digits and area code, and formats for E.164, national and international display', function (string $input, string $value, string $areaCode, string $e164, string $format, string $international): void {
        $phone = PhoneNumber::from($input);

        expect($phone->value)->toBe($value)
            ->and($phone->areaCode())->toBe($areaCode)
            ->and($phone->toE164())->toBe($e164)
            ->and($phone->format())->toBe($format)
            ->and($phone->formatInternational())->toBe($international);
    })->with('landline numbers');

    it('is a landline according to type() and every is*() method', function (string $input): void {
        $phone = PhoneNumber::from($input);

        expect($phone->type())->toBe(PhoneType::Landline)
            ->and($phone->isMobile())->toBeFalse()
            ->and($phone->isLandline())->toBeTrue()
            ->and($phone->isService())->toBeFalse();
    })->with('landline numbers');

    it('round-trips through E.164 and both display formats', function (string $input): void {
        $phone = PhoneNumber::from($input);
        $e164 = $phone->toE164();
        $international = $phone->formatInternational();

        expect($e164)->not->toBeNull()
            ->and($international)->not->toBeNull()
            ->and(PhoneNumber::from((string) $e164)->equals($phone))->toBeTrue()
            ->and(PhoneNumber::from($phone->format())->equals($phone))->toBeTrue()
            ->and(PhoneNumber::from((string) $international)->equals($phone))->toBeTrue();
    })->with('landline numbers');
});

describe('service numbers', function (): void {
    it('accepts each typed form', function (string $input, string $value): void {
        expect(PhoneNumber::isValid($input))->toBeTrue()
            ->and(PhoneNumber::tryFrom($input)?->value)->toBe($value);
    })->with('service numbers');

    it('parses to the national digits and formats for national display', function (string $input, string $value, string $format): void {
        $phone = PhoneNumber::from($input);

        expect($phone->value)->toBe($value)
            ->and($phone->format())->toBe($format);
    })->with('service numbers');

    it('is a service number according to type() and every is*() method', function (string $input): void {
        $phone = PhoneNumber::from($input);

        expect($phone->type())->toBe(PhoneType::Service)
            ->and($phone->isMobile())->toBeFalse()
            ->and($phone->isLandline())->toBeFalse()
            ->and($phone->isService())->toBeTrue();
    })->with('service numbers');

    it('has no area code, E.164 or international form, because it cannot be dialled from overseas', function (string $input): void {
        $phone = PhoneNumber::from($input);

        expect($phone->areaCode())->toBeNull()
            ->and($phone->toE164())->toBeNull()
            ->and($phone->formatInternational())->toBeNull();
    })->with('service numbers');

    it('round-trips through its national display format', function (string $input): void {
        $phone = PhoneNumber::from($input);

        expect(PhoneNumber::from($phone->format())->equals($phone))->toBeTrue();
    })->with('service numbers');
});

describe('international prefix', function (): void {
    it('replaces +61 or 61 with the trunk zero', function (string $input, string $value): void {
        expect(PhoneNumber::from($input)->value)->toBe($value);
    })->with([
        'a mobile after +61' => ['+61 491 570 156', '0491570156'],
        'a mobile after 61' => ['61491570156', '0491570156'],
        'a landline after +61' => ['+61 7 5550 1234', '0755501234'],
        'a landline after 61' => ['61 2 5550 1234', '0255501234'],
    ]);

    it('drops +61 or 61 in front of the (0) trunk form', function (string $input, string $value): void {
        expect(PhoneNumber::from($input)->value)->toBe($value);
    })->with([
        'a landline' => ['+61 (0)2 5550 1234', '0255501234'],
        'a mobile' => ['+61 (0)491 570 156', '0491570156'],
        'without the plus sign' => ['61 (0)8 5550 1234', '0855501234'],
        'without the parentheses' => ['+610491570156', '0491570156'],
    ]);

    it('rejects +61 followed by the wrong number of digits', function (string $input): void {
        expect(PhoneNumber::isValid($input))->toBeFalse()
            ->and(PhoneNumber::tryFrom($input))->toBeNull();
    })->with([
        'too few digits' => '+61 4915 7015',
        'too many digits' => '+61 491 570 1560',
        'too few digits after (0)' => '+61 (0)491 570 15',
        'too many digits after (0)' => '+61 (0)491 570 1560',
        'a 1800 number' => '+61 1800 160 401',
    ]);

    it('rejects any other country code', function (): void {
        expect(PhoneNumber::isValid('+64 21 123 4567'))->toBeFalse()
            ->and(fn () => PhoneNumber::from('+64 21 123 4567'))
            ->toThrow(InvalidValueException::class, 'The given value is not a valid phone number.');
    });
});

describe('isValid()', function (): void {
    it('rejects an invalid phone number', function (string $input): void {
        expect(PhoneNumber::isValid($input))->toBeFalse();
    })->with('invalid phone numbers');

    it('rejects blank input', function (string $input): void {
        expect(PhoneNumber::isValid($input))->toBeFalse();
    })->with('blank phone inputs');

    it('rejects null', function (): void {
        expect(PhoneNumber::isValid(null))->toBeFalse();
    });
});

describe('from()', function (): void {
    it('creates a phone number holding the national digits', function (): void {
        $phone = PhoneNumber::from('+61 (0)491 570 156');

        expect($phone)->toBeInstanceOf(PhoneNumber::class)
            ->and($phone->value)->toBe('0491570156');
    });

    it('throws an InvalidValueException for an invalid phone number', function (string $input): void {
        expect(fn () => PhoneNumber::from($input))
            ->toThrow(InvalidValueException::class, 'The given value is not a valid phone number.');
    })->with('invalid phone numbers');

    it('throws an InvalidValueException for blank input', function (string $input): void {
        expect(fn () => PhoneNumber::from($input))
            ->toThrow(InvalidValueException::class, 'The given value is not a valid phone number.');
    })->with('blank phone inputs');

    it('does not reveal the input in the exception message', function (string $input): void {
        $digits = (string) preg_replace('/\D+/', '', $input);

        expect(fn () => PhoneNumber::from($input))->toThrow(function (InvalidValueException $exception) use ($input, $digits): void {
            expect($exception->getMessage())
                ->not->toContain($input)
                ->when($digits !== '', fn (Expectation $message) => $message->not->toContain($digits));
        });
    })->with('invalid phone numbers');
});

describe('tryFrom()', function (): void {
    it('returns null for null', function (): void {
        expect(PhoneNumber::tryFrom(null))->toBeNull();
    });

    it('returns null for blank input', function (string $input): void {
        expect(PhoneNumber::tryFrom($input))->toBeNull();
    })->with('blank phone inputs');

    it('returns null for an invalid phone number', function (string $input): void {
        expect(PhoneNumber::tryFrom($input))->toBeNull();
    })->with('invalid phone numbers');
});

describe('equals()', function (): void {
    it('is equal to the same number written differently', function (string $a, string $b): void {
        expect(PhoneNumber::from($a)->equals(PhoneNumber::from($b)))->toBeTrue();
    })->with([
        'a mobile, national and (0) trunk forms' => ['0491-570-156', '+61 (0)491 570 156'],
        'a landline, national and E.164 forms' => ['(02) 5550 1234', '+61255501234'],
        'a 13 number with different grouping' => ['13 12 34', '13 1234'],
    ]);

    it('is not equal to a different number', function (string $a, string $b): void {
        expect(PhoneNumber::from($a)->equals(PhoneNumber::from($b)))->toBeFalse();
    })->with([
        'two mobiles' => ['0491 570 156', '0491 570 157'],
        'the same local number in another area' => ['(02) 5550 1234', '(03) 5550 1234'],
        '1800 and 1300 with the same digits after them' => ['1800 975 707', '1300 975 707'],
        'a mobile and a landline' => ['0491 570 156', '(02) 5550 1234'],
    ]);
});

describe('string conversion', function (): void {
    it('is stringable', function (): void {
        expect(PhoneNumber::from('0491 570 156'))->toBeInstanceOf(Stringable::class);
    });

    it('casts to the national digits', function (): void {
        $phone = PhoneNumber::from('+61 491 570 156');

        expect((string) $phone)->toBe('0491570156')
            ->and("Call {$phone}")->toBe('Call 0491570156');
    });
});

describe('JSON serialisation', function (): void {
    it('is JSON serialisable', function (): void {
        expect(PhoneNumber::from('0491 570 156'))->toBeInstanceOf(JsonSerializable::class);
    });

    it('serialises to the national digits', function (): void {
        expect(PhoneNumber::from('(02) 5550 1234')->jsonSerialize())->toBe('0255501234');
    });

    it('encodes as a JSON string', function (): void {
        expect(json_encode(PhoneNumber::from('13 12 34')))->toBe('"131234"')
            ->and(json_encode(['phone' => PhoneNumber::from('+61 491 570 156')]))->toBe('{"phone":"0491570156"}');
    });
});

it('can only be created through its named constructors', function (): void {
    expect(fn () => new PhoneNumber('0491570156'))->toThrow(Error::class, 'Call to private');
});
