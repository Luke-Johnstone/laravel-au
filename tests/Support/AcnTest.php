<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Digits;

mutates(Acn::class, Digits::class);

dataset('valid acns', [
    'ASIC example 000 000 019' => '000 000 019',
    'ASIC example 000 250 000' => '000 250 000',
    'ASIC example 000 500 005' => '000 500 005',
    'ASIC example 000 750 005' => '000 750 005',
    'ASIC example 001 000 004' => '001 000 004',
    'ASIC example 001 250 004' => '001 250 004',
    'ASIC example 001 500 009' => '001 500 009',
    'ASIC example 001 749 999' => '001 749 999',
    'ASIC example 001 999 999' => '001 999 999',
    'ASIC example 002 249 998' => '002 249 998',
    'ASIC example 010 749 961' => '010 749 961',
    'ACN within the ABN Lookup documentation example' => '004 085 616',
]);

dataset('invalid acns', [
    'bad check digit' => '000 000 018',
    'sequential digits' => '123 456 789',
    'too short (8 digits)' => '00000019',
    'too long (10 digits)' => '0000000190',
    'an ABN rather than an ACN' => '53 004 085 616',
    'a letter in place of a digit' => '000 000 01a',
    'a letter prefix' => 'ACN 000 000 019',
    'tab separators' => "000\t000\t019",
    'newline separators' => "000\n000\n019",
    'a trailing newline' => "000000019\n",
    'slash separators' => '000/000/019',
    'underscore separators' => '000_000_019',
    'a leading plus sign' => '+000000019',
    'thin space separators (U+2009)' => "000\u{2009}000\u{2009}019",
    'narrow no-break space separators (U+202F)' => "000\u{202F}000\u{202F}019",
]);

dataset('blank acn inputs', [
    'an empty string' => '',
    'spaces only' => '   ',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
    'separators only' => ' -. ',
]);

dataset('normalisable acns', [
    'canonical digits' => ['000000019', '000000019'],
    'spaces' => ['000 000 019', '000000019'],
    'hyphens' => ['004-085-616', '004085616'],
    'dots' => ['010.749.961', '010749961'],
    'non-breaking spaces (U+00A0)' => ["001\u{00A0}999\u{00A0}999", '001999999'],
    'mixed separators' => ["002\u{00A0}249-998.", '002249998'],
    'repeated separators' => ['001  250--004', '001250004'],
    'leading and trailing spaces' => ['  000 000 019  ', '000000019'],
]);

dataset('formatted acns', [
    'canonical digits' => ['000000019', '000 000 019'],
    'hyphenated' => ['004-085-616', '004 085 616'],
    'non-breaking spaces' => ["010\u{00A0}749\u{00A0}961", '010 749 961'],
]);

describe('isValid()', function (): void {
    it('accepts a valid ACN', function (string $input): void {
        expect(Acn::isValid($input))->toBeTrue();
    })->with('valid acns');

    it('rejects an invalid ACN', function (string $input): void {
        expect(Acn::isValid($input))->toBeFalse();
    })->with('invalid acns');

    it('rejects blank input', function (string $input): void {
        expect(Acn::isValid($input))->toBeFalse();
    })->with('blank acn inputs');

    it('rejects null', function (): void {
        expect(Acn::isValid(null))->toBeFalse();
    });
});

describe('normalisation', function (): void {
    it('strips spaces, hyphens, dots and non-breaking spaces', function (string $input, string $digits): void {
        expect(Acn::isValid($input))->toBeTrue()
            ->and(Acn::from($input)->value)->toBe($digits)
            ->and(Acn::tryFrom($input)?->value)->toBe($digits);
    })->with('normalisable acns');
});

describe('from()', function (): void {
    it('creates an ACN holding only the digits, keeping leading zeros', function (): void {
        $acn = Acn::from('000 000 019');

        expect($acn)->toBeInstanceOf(Acn::class)
            ->and($acn->value)->toBe('000000019');
    });

    it('throws an InvalidValueException for an invalid ACN', function (string $input): void {
        expect(fn () => Acn::from($input))
            ->toThrow(InvalidValueException::class, 'The given value is not a valid ACN.');
    })->with('invalid acns');

    it('throws an InvalidValueException for blank input', function (string $input): void {
        expect(fn () => Acn::from($input))->toThrow(InvalidValueException::class);
    })->with('blank acn inputs');

    it('does not reveal the input in the exception message', function (string $input): void {
        $digits = (string) preg_replace('/\D+/', '', $input);

        expect(fn () => Acn::from($input))->toThrow(function (InvalidValueException $exception) use ($input, $digits): void {
            expect($exception->getMessage())
                ->not->toContain($input)
                ->not->toContain($digits);
        });
    })->with('invalid acns');
});

describe('tryFrom()', function (): void {
    it('returns an ACN for a valid ACN', function (string $input): void {
        expect(Acn::tryFrom($input))->toBeInstanceOf(Acn::class);
    })->with('valid acns');

    it('returns null for null', function (): void {
        expect(Acn::tryFrom(null))->toBeNull();
    });

    it('returns null for blank input', function (string $input): void {
        expect(Acn::tryFrom($input))->toBeNull();
    })->with('blank acn inputs');

    it('returns null for an invalid ACN', function (string $input): void {
        expect(Acn::tryFrom($input))->toBeNull();
    })->with('invalid acns');
});

describe('format()', function (): void {
    it('groups the digits as 3-3-3', function (string $input, string $formatted): void {
        expect(Acn::from($input)->format())->toBe($formatted);
    })->with('formatted acns');
});

describe('equals()', function (): void {
    it('is equal to the same ACN written differently', function (): void {
        expect(Acn::from('000000019')->equals(Acn::from('000-000.019')))->toBeTrue();
    });

    it('is not equal to a different ACN', function (): void {
        expect(Acn::from('000 000 019')->equals(Acn::from('000 250 000')))->toBeFalse();
    });
});

describe('string conversion', function (): void {
    it('is stringable', function (): void {
        expect(Acn::from('004 085 616'))->toBeInstanceOf(Stringable::class);
    });

    it('casts to the canonical digits', function (): void {
        $acn = Acn::from('004 085 616');

        expect((string) $acn)->toBe('004085616')
            ->and("ACN {$acn}")->toBe('ACN 004085616');
    });
});

describe('JSON serialisation', function (): void {
    it('is JSON serialisable', function (): void {
        expect(Acn::from('004 085 616'))->toBeInstanceOf(JsonSerializable::class);
    });

    it('serialises to the canonical digits', function (): void {
        expect(Acn::from('004 085 616')->jsonSerialize())->toBe('004085616');
    });

    it('encodes as a JSON string, keeping leading zeros', function (): void {
        expect(json_encode(Acn::from('000 000 019')))->toBe('"000000019"')
            ->and(json_encode(['acn' => Acn::from('004-085-616')]))->toBe('{"acn":"004085616"}');
    });
});

it('can only be created through its named constructors', function (): void {
    expect(fn () => new Acn('000000019'))->toThrow(Error::class, 'Call to private');
});
