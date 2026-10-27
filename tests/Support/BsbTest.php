<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Bsb;
use EastpointLabs\LaravelAu\Support\Digits;

mutates(Bsb::class, Digits::class);

dataset('valid bsbs', [
    'hyphenated' => '062-000',
    'any six digits, as there is no checksum' => '123-456',
    'all nines' => '999 999',
]);

dataset('invalid bsbs', [
    'too short (5 digits)' => '06200',
    'too short, hyphenated (5 digits)' => '062-00',
    'too long (7 digits)' => '0620001',
    'too long, hyphenated (7 digits)' => '062-0001',
    'a letter in place of a digit' => '062-00a',
    'a letter prefix' => 'BSB 062-000',
    'a tab separator' => "062\t000",
    'a newline separator' => "062\n000",
    'a trailing newline' => "062000\n",
    'a slash separator' => '062/000',
    'an underscore separator' => '062_000',
    'a leading plus sign' => '+062000',
    'a thin space separator (U+2009)' => "062\u{2009}000",
    'a narrow no-break space separator (U+202F)' => "062\u{202F}000",
]);

dataset('blank bsb inputs', [
    'an empty string' => '',
    'spaces only' => '   ',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
    'separators only' => ' -. ',
]);

dataset('normalisable bsbs', [
    'canonical digits' => ['062000', '062000'],
    'a hyphen' => ['062-000', '062000'],
    'a space' => ['062 000', '062000'],
    'a dot' => ['062.000', '062000'],
    'a non-breaking space (U+00A0)' => ["062\u{00A0}000", '062000'],
    'mixed separators' => ["06-2.0\u{00A0}00", '062000'],
    'repeated separators' => ['062 - 000', '062000'],
    'leading and trailing spaces' => ['  062-000  ', '062000'],
]);

dataset('formatted bsbs', [
    'canonical digits' => ['062000', '062-000'],
    'already hyphenated' => ['062-000', '062-000'],
    'a space' => ['123 456', '123-456'],
    'a non-breaking space' => ["999\u{00A0}999", '999-999'],
]);

describe('isValid()', function (): void {
    it('accepts a six-digit BSB', function (string $input): void {
        expect(Bsb::isValid($input))->toBeTrue();
    })->with('valid bsbs');

    it('rejects an invalid BSB', function (string $input): void {
        expect(Bsb::isValid($input))->toBeFalse();
    })->with('invalid bsbs');

    it('rejects blank input', function (string $input): void {
        expect(Bsb::isValid($input))->toBeFalse();
    })->with('blank bsb inputs');

    it('rejects null', function (): void {
        expect(Bsb::isValid(null))->toBeFalse();
    });
});

describe('normalisation', function (): void {
    it('strips spaces, hyphens, dots and non-breaking spaces', function (string $input, string $digits): void {
        expect(Bsb::isValid($input))->toBeTrue()
            ->and(Bsb::from($input)->value)->toBe($digits)
            ->and(Bsb::tryFrom($input)?->value)->toBe($digits);
    })->with('normalisable bsbs');
});

describe('from()', function (): void {
    it('creates a BSB holding only the digits', function (): void {
        $bsb = Bsb::from('062-000');

        expect($bsb)->toBeInstanceOf(Bsb::class)
            ->and($bsb->value)->toBe('062000');
    });

    it('throws an InvalidValueException for an invalid BSB', function (string $input): void {
        expect(fn () => Bsb::from($input))
            ->toThrow(InvalidValueException::class, 'The given value is not a valid BSB.');
    })->with('invalid bsbs');

    it('throws an InvalidValueException for blank input', function (string $input): void {
        expect(fn () => Bsb::from($input))->toThrow(InvalidValueException::class);
    })->with('blank bsb inputs');

    it('does not reveal the input in the exception message', function (string $input): void {
        $digits = (string) preg_replace('/\D+/', '', $input);

        expect(fn () => Bsb::from($input))->toThrow(function (InvalidValueException $exception) use ($input, $digits): void {
            expect($exception->getMessage())
                ->not->toContain($input)
                ->not->toContain($digits);
        });
    })->with('invalid bsbs');
});

describe('tryFrom()', function (): void {
    it('returns a BSB for a valid BSB', function (string $input): void {
        expect(Bsb::tryFrom($input))->toBeInstanceOf(Bsb::class);
    })->with('valid bsbs');

    it('returns null for null', function (): void {
        expect(Bsb::tryFrom(null))->toBeNull();
    });

    it('returns null for blank input', function (string $input): void {
        expect(Bsb::tryFrom($input))->toBeNull();
    })->with('blank bsb inputs');

    it('returns null for an invalid BSB', function (string $input): void {
        expect(Bsb::tryFrom($input))->toBeNull();
    })->with('invalid bsbs');
});

describe('format()', function (): void {
    it('separates the two groups of three digits with a hyphen', function (string $input, string $formatted): void {
        expect(Bsb::from($input)->format())->toBe($formatted);
    })->with('formatted bsbs');
});

describe('equals()', function (): void {
    it('is equal to the same BSB written differently', function (): void {
        expect(Bsb::from('062-000')->equals(Bsb::from('062000')))->toBeTrue();
    });

    it('is not equal to a different BSB', function (): void {
        expect(Bsb::from('062-000')->equals(Bsb::from('123-456')))->toBeFalse();
    });
});

describe('string conversion', function (): void {
    it('is stringable', function (): void {
        expect(Bsb::from('062-000'))->toBeInstanceOf(Stringable::class);
    });

    it('casts to the canonical digits', function (): void {
        $bsb = Bsb::from('062-000');

        expect((string) $bsb)->toBe('062000')
            ->and("BSB {$bsb}")->toBe('BSB 062000');
    });
});

describe('JSON serialisation', function (): void {
    it('is JSON serialisable', function (): void {
        expect(Bsb::from('062-000'))->toBeInstanceOf(JsonSerializable::class);
    });

    it('serialises to the canonical digits', function (): void {
        expect(Bsb::from('062-000')->jsonSerialize())->toBe('062000');
    });

    it('encodes as a JSON string, keeping leading zeros', function (): void {
        expect(json_encode(Bsb::from('062-000')))->toBe('"062000"')
            ->and(json_encode(['bsb' => Bsb::from('062 000')]))->toBe('{"bsb":"062000"}');
    });
});

it('can only be created through its named constructors', function (): void {
    expect(fn () => new Bsb('062000'))->toThrow(Error::class, 'Call to private');
});
