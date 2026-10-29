<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Digits;

mutates(Abn::class, Digits::class);

// Input, digits, format().
dataset('valid abns', [
    'Australian Taxation Office, with spaces' => ['51 824 753 556', '51824753556', '51 824 753 556'],
    'ABN Lookup documentation example, with hyphens' => ['53-004-085-616', '53004085616', '53 004 085 616'],
    'checksum-valid example, with dots' => ['83.914.571.673', '83914571673', '83 914 571 673'],
    'non-breaking spaces (U+00A0)' => ["51\u{00A0}824\u{00A0}753\u{00A0}556", '51824753556', '51 824 753 556'],
    'plain digits' => ['53004085616', '53004085616', '53 004 085 616'],
    'mixed, repeated and surrounding separators' => [" 83  914-571.\u{00A0}673 ", '83914571673', '83 914 571 673'],
]);

dataset('invalid abns', [
    'a bad checksum' => '51 824 753 557',
    'a checksum failure with a zero body' => '11 000 000 000',
    'too short (10 digits)' => '5182475355',
    'too long (12 digits)' => '518247535560',
    'a letter in place of a digit' => '51 824 753 55a',
    'slash separators' => '51/824/753/556',
    'tab separators' => "51\t824\t753\t556",
    'a trailing newline' => "51824753556\n",
    'thin space separators (U+2009)' => "51\u{2009}824\u{2009}753\u{2009}556",
    'narrow no-break space separators (U+202F)' => "51\u{202F}824\u{202F}753\u{202F}556",
]);

dataset('blank abns', [
    'an empty string' => '',
    'spaces only' => '   ',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
    'separators only' => ' -. ',
]);

it('accepts a valid ABN and keeps only its digits', function (string $input, string $digits, string $formatted): void {
    $abn = Abn::from($input);

    expect(Abn::isValid($input))->toBeTrue()
        ->and(Abn::tryFrom($input)?->value)->toBe($digits)
        ->and($abn->value)->toBe($digits)
        ->and($abn->format())->toBe($formatted)
        ->and(Abn::from($formatted)->equals($abn))->toBeTrue();
})->with('valid abns');

it('rejects an invalid ABN without revealing it', function (string $input): void {
    expect(Abn::isValid($input))->toBeFalse()
        ->and(Abn::tryFrom($input))->toBeNull()
        ->and(fn () => Abn::from($input))->toThrow(function (InvalidValueException $exception) use ($input): void {
            expect($exception->getMessage())
                ->toBe('The given value is not a valid ABN.')
                ->not->toContain($input)
                ->not->toMatch('/\d/');
        });
})->with('invalid abns');

it('rejects blank input', function (string $input): void {
    expect(Abn::isValid($input))->toBeFalse()
        ->and(Abn::tryFrom($input))->toBeNull()
        ->and(fn () => Abn::from($input))->toThrow(InvalidValueException::class, 'The given value is not a valid ABN.');
})->with('blank abns');

it('treats null as invalid', function (): void {
    expect(Abn::isValid(null))->toBeFalse()
        ->and(Abn::tryFrom(null))->toBeNull();
});

it('finds the ACN in the last nine digits', function (): void {
    $acn = Abn::from('53 004 085 616')->acn();

    expect($acn)->toBeInstanceOf(Acn::class)
        ->and($acn?->value)->toBe('004085616');
});

it('finds no ACN when the last nine digits fail the ACN check', function (): void {
    expect(Abn::from('51 824 753 556')->acn())->toBeNull()
        ->and(Abn::from('83 914 571 673')->acn())->toBeNull();
});

it('equals the same ABN written differently, and no other', function (): void {
    $abn = Abn::from('51 824 753 556');

    expect($abn->equals(Abn::from('51-824-753.556')))->toBeTrue()
        ->and($abn->equals(Abn::from('53 004 085 616')))->toBeFalse();
});

it('casts and JSON-encodes to its digits', function (): void {
    $abn = Abn::from('51 824 753 556');

    expect((string) $abn)->toBe('51824753556')
        ->and(json_encode(['abn' => $abn]))->toBe('{"abn":"51824753556"}');
});

it('can only be created through its named constructors', function (): void {
    expect(fn () => new Abn('51824753556'))->toThrow(Error::class, 'Call to private');
});
