<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Digits;

mutates(Acn::class, Digits::class);

// Input, digits, format(). All of ASIC's published examples; the first seven also cover each
// way of writing an ACN.
dataset('valid acns', [
    '000 000 019, with spaces' => ['000 000 019', '000000019', '000 000 019'],
    '000 250 000, with hyphens' => ['000-250-000', '000250000', '000 250 000'],
    '000 500 005, with dots' => ['000.500.005', '000500005', '000 500 005'],
    '000 750 005, with non-breaking spaces (U+00A0)' => ["000\u{00A0}750\u{00A0}005", '000750005', '000 750 005'],
    '001 000 004, as plain digits' => ['001000004', '001000004', '001 000 004'],
    '001 250 004, with mixed and repeated separators' => ["001 \u{00A0}250--004.", '001250004', '001 250 004'],
    '001 500 009, with surrounding spaces' => ['  001 500 009  ', '001500009', '001 500 009'],
    '001 749 999' => ['001 749 999', '001749999', '001 749 999'],
    '001 999 999' => ['001 999 999', '001999999', '001 999 999'],
    '002 249 998' => ['002 249 998', '002249998', '002 249 998'],
    '010 749 961' => ['010 749 961', '010749961', '010 749 961'],
]);

dataset('invalid acns', [
    'a bad check digit' => '000 000 018',
    'sequential digits' => '123 456 789',
    'too short (8 digits)' => '00000019',
    'too long (10 digits)' => '0000000190',
    'a letter in place of a digit' => '000 000 01a',
    'slash separators' => '000/000/019',
    'tab separators' => "000\t000\t019",
    'a trailing newline' => "000000019\n",
    'thin space separators (U+2009)' => "000\u{2009}000\u{2009}019",
    'narrow no-break space separators (U+202F)' => "000\u{202F}000\u{202F}019",
]);

dataset('blank acns', [
    'an empty string' => '',
    'spaces only' => '   ',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
    'separators only' => ' -. ',
]);

it('accepts a valid ACN and keeps only its digits', function (string $input, string $digits, string $formatted): void {
    $acn = Acn::from($input);

    expect(Acn::isValid($input))->toBeTrue()
        ->and(Acn::tryFrom($input)?->value)->toBe($digits)
        ->and($acn->value)->toBe($digits)
        ->and($acn->format())->toBe($formatted)
        ->and(Acn::from($formatted)->equals($acn))->toBeTrue();
})->with('valid acns');

it('rejects an invalid ACN without revealing it', function (string $input): void {
    expect(Acn::isValid($input))->toBeFalse()
        ->and(Acn::tryFrom($input))->toBeNull()
        ->and(fn () => Acn::from($input))->toThrow(function (InvalidValueException $exception) use ($input): void {
            expect($exception->getMessage())
                ->toBe('The given value is not a valid ACN.')
                ->not->toContain($input)
                ->not->toMatch('/\d/');
        });
})->with('invalid acns');

it('rejects blank input', function (string $input): void {
    expect(Acn::isValid($input))->toBeFalse()
        ->and(Acn::tryFrom($input))->toBeNull()
        ->and(fn () => Acn::from($input))->toThrow(InvalidValueException::class, 'The given value is not a valid ACN.');
})->with('blank acns');

it('treats null as invalid', function (): void {
    expect(Acn::isValid(null))->toBeFalse()
        ->and(Acn::tryFrom(null))->toBeNull();
});

it('equals the same ACN written differently, and no other', function (): void {
    $acn = Acn::from('000 000 019');

    expect($acn->equals(Acn::from('000-000.019')))->toBeTrue()
        ->and($acn->equals(Acn::from('000 250 000')))->toBeFalse();
});

it('casts and JSON-encodes to its digits, keeping leading zeros', function (): void {
    $acn = Acn::from('000 000 019');

    expect((string) $acn)->toBe('000000019')
        ->and(json_encode(['acn' => $acn]))->toBe('{"acn":"000000019"}');
});

it('can only be created through its named constructors', function (): void {
    expect(fn () => new Acn('000000019'))->toThrow(Error::class, 'Call to private');
});
