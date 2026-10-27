<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Digits;

mutates(Abn::class, Digits::class);

dataset('valid abns', [
    'Australian Taxation Office' => '51 824 753 556',
    'ABN Lookup documentation example' => '53 004 085 616',
    'checksum-valid example' => '83 914 571 673',
]);

dataset('invalid abns', [
    'bad checksum' => '51 824 753 557',
    'checksum failure with a zero body' => '11 000 000 000',
    'too short (10 digits)' => '5182475355',
    'too long (12 digits)' => '518247535560',
    'an ACN rather than an ABN' => '004 085 616',
    'a letter in place of a digit' => '51 824 753 55a',
    'a letter prefix' => 'ABN 51 824 753 556',
    'tab separators' => "51\t824\t753\t556",
    'newline separators' => "51\n824\n753\n556",
    'a trailing newline' => "51824753556\n",
    'slash separators' => '51/824/753/556',
    'underscore separators' => '51_824_753_556',
    'a leading plus sign' => '+51824753556',
    'thin space separators (U+2009)' => "51\u{2009}824\u{2009}753\u{2009}556",
    'narrow no-break space separators (U+202F)' => "51\u{202F}824\u{202F}753\u{202F}556",
]);

dataset('blank abn inputs', [
    'an empty string' => '',
    'spaces only' => '   ',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
    'separators only' => ' -. ',
]);

dataset('normalisable abns', [
    'canonical digits' => ['51824753556', '51824753556'],
    'spaces' => ['51 824 753 556', '51824753556'],
    'hyphens' => ['51-824-753-556', '51824753556'],
    'dots' => ['51.824.753.556', '51824753556'],
    'non-breaking spaces (U+00A0)' => ["51\u{00A0}824\u{00A0}753\u{00A0}556", '51824753556'],
    'mixed separators' => ["53\u{00A0}004-085.616", '53004085616'],
    'repeated separators' => ['83  914--571..673', '83914571673'],
    'leading and trailing spaces' => ['  51824753556  ', '51824753556'],
]);

dataset('formatted abns', [
    'canonical digits' => ['51824753556', '51 824 753 556'],
    'hyphenated' => ['53-004-085-616', '53 004 085 616'],
    'non-breaking spaces' => ["83\u{00A0}914\u{00A0}571\u{00A0}673", '83 914 571 673'],
]);

describe('isValid()', function (): void {
    it('accepts a valid ABN', function (string $input): void {
        expect(Abn::isValid($input))->toBeTrue();
    })->with('valid abns');

    it('rejects an invalid ABN', function (string $input): void {
        expect(Abn::isValid($input))->toBeFalse();
    })->with('invalid abns');

    it('rejects blank input', function (string $input): void {
        expect(Abn::isValid($input))->toBeFalse();
    })->with('blank abn inputs');

    it('rejects null', function (): void {
        expect(Abn::isValid(null))->toBeFalse();
    });
});

describe('normalisation', function (): void {
    it('strips spaces, hyphens, dots and non-breaking spaces', function (string $input, string $digits): void {
        expect(Abn::isValid($input))->toBeTrue()
            ->and(Abn::from($input)->value)->toBe($digits)
            ->and(Abn::tryFrom($input)?->value)->toBe($digits);
    })->with('normalisable abns');
});

describe('from()', function (): void {
    it('creates an ABN holding only the digits', function (): void {
        $abn = Abn::from('51 824 753 556');

        expect($abn)->toBeInstanceOf(Abn::class)
            ->and($abn->value)->toBe('51824753556');
    });

    it('throws an InvalidValueException for an invalid ABN', function (string $input): void {
        expect(fn () => Abn::from($input))
            ->toThrow(InvalidValueException::class, 'The given value is not a valid ABN.');
    })->with('invalid abns');

    it('throws an InvalidValueException for blank input', function (string $input): void {
        expect(fn () => Abn::from($input))->toThrow(InvalidValueException::class);
    })->with('blank abn inputs');

    it('does not reveal the input in the exception message', function (string $input): void {
        $digits = (string) preg_replace('/\D+/', '', $input);

        expect(fn () => Abn::from($input))->toThrow(function (InvalidValueException $exception) use ($input, $digits): void {
            expect($exception->getMessage())
                ->not->toContain($input)
                ->not->toContain($digits);
        });
    })->with('invalid abns');
});

describe('tryFrom()', function (): void {
    it('returns an ABN for a valid ABN', function (string $input): void {
        expect(Abn::tryFrom($input))->toBeInstanceOf(Abn::class);
    })->with('valid abns');

    it('returns null for null', function (): void {
        expect(Abn::tryFrom(null))->toBeNull();
    });

    it('returns null for blank input', function (string $input): void {
        expect(Abn::tryFrom($input))->toBeNull();
    })->with('blank abn inputs');

    it('returns null for an invalid ABN', function (string $input): void {
        expect(Abn::tryFrom($input))->toBeNull();
    })->with('invalid abns');
});

describe('format()', function (): void {
    it('groups the digits as 2-3-3-3', function (string $input, string $formatted): void {
        expect(Abn::from($input)->format())->toBe($formatted);
    })->with('formatted abns');
});

describe('acn()', function (): void {
    it('returns the ACN held in the last nine digits', function (): void {
        $acn = Abn::from('53 004 085 616')->acn();

        expect($acn)->toBeInstanceOf(Acn::class)
            ->and($acn?->value)->toBe('004085616')
            ->and($acn?->format())->toBe('004 085 616');
    });

    it('returns null when the last nine digits are not a valid ACN', function (string $input): void {
        expect(Abn::from($input)->acn())->toBeNull();
    })->with([
        'Australian Taxation Office' => '51 824 753 556',
        'checksum-valid example' => '83 914 571 673',
    ]);
});

describe('equals()', function (): void {
    it('is equal to the same ABN written differently', function (): void {
        expect(Abn::from('51824753556')->equals(Abn::from('51 824-753.556')))->toBeTrue();
    });

    it('is not equal to a different ABN', function (): void {
        expect(Abn::from('51 824 753 556')->equals(Abn::from('53 004 085 616')))->toBeFalse();
    });
});

describe('string conversion', function (): void {
    it('is stringable', function (): void {
        expect(Abn::from('51 824 753 556'))->toBeInstanceOf(Stringable::class);
    });

    it('casts to the canonical digits', function (): void {
        $abn = Abn::from('51 824 753 556');

        expect((string) $abn)->toBe('51824753556')
            ->and("ABN {$abn}")->toBe('ABN 51824753556');
    });
});

describe('JSON serialisation', function (): void {
    it('is JSON serialisable', function (): void {
        expect(Abn::from('51 824 753 556'))->toBeInstanceOf(JsonSerializable::class);
    });

    it('serialises to the canonical digits', function (): void {
        expect(Abn::from('51 824 753 556')->jsonSerialize())->toBe('51824753556');
    });

    it('encodes as a JSON string', function (): void {
        expect(json_encode(Abn::from('51 824 753 556')))->toBe('"51824753556"')
            ->and(json_encode(['abn' => Abn::from('53-004-085-616')]))->toBe('{"abn":"53004085616"}');
    });
});

it('can only be created through its named constructors', function (): void {
    expect(fn () => new Abn('51824753556'))->toThrow(Error::class, 'Call to private');
});
