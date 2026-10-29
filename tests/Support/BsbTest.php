<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Bsb;
use EastpointLabs\LaravelAu\Support\Digits;

mutates(Bsb::class, Digits::class);

// Input, digits, format().
dataset('valid bsbs', [
    'a hyphen' => ['062-000', '062000', '062-000'],
    'a space' => ['062 000', '062000', '062-000'],
    'a dot' => ['062.000', '062000', '062-000'],
    'a non-breaking space (U+00A0)' => ["062\u{00A0}000", '062000', '062-000'],
    'plain digits, any six as there is no checksum' => ['123456', '123456', '123-456'],
    'mixed, repeated and surrounding separators' => [" 06-2.\u{00A0}0 00 ", '062000', '062-000'],
]);

dataset('invalid bsbs', [
    'too short (5 digits)' => '06200',
    'too long (7 digits)' => '0620001',
    'a letter in place of a digit' => '062-00a',
    'a slash separator' => '062/000',
    'a tab separator' => "062\t000",
    'a trailing newline' => "062000\n",
    'a thin space separator (U+2009)' => "062\u{2009}000",
    'a narrow no-break space separator (U+202F)' => "062\u{202F}000",
]);

dataset('blank bsbs', [
    'an empty string' => '',
    'spaces only' => '   ',
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
    'separators only' => ' -. ',
]);

it('accepts a six-digit BSB and keeps only its digits', function (string $input, string $digits, string $formatted): void {
    $bsb = Bsb::from($input);

    expect(Bsb::isValid($input))->toBeTrue()
        ->and(Bsb::tryFrom($input)?->value)->toBe($digits)
        ->and($bsb->value)->toBe($digits)
        ->and($bsb->format())->toBe($formatted)
        ->and(Bsb::from($formatted)->equals($bsb))->toBeTrue();
})->with('valid bsbs');

it('rejects an invalid BSB without revealing it', function (string $input): void {
    expect(Bsb::isValid($input))->toBeFalse()
        ->and(Bsb::tryFrom($input))->toBeNull()
        ->and(fn () => Bsb::from($input))->toThrow(function (InvalidValueException $exception) use ($input): void {
            expect($exception->getMessage())
                ->toBe('The given value is not a valid BSB.')
                ->not->toContain($input)
                ->not->toMatch('/\d/');
        });
})->with('invalid bsbs');

it('rejects blank input', function (string $input): void {
    expect(Bsb::isValid($input))->toBeFalse()
        ->and(Bsb::tryFrom($input))->toBeNull()
        ->and(fn () => Bsb::from($input))->toThrow(InvalidValueException::class, 'The given value is not a valid BSB.');
})->with('blank bsbs');

it('treats null as invalid', function (): void {
    expect(Bsb::isValid(null))->toBeFalse()
        ->and(Bsb::tryFrom(null))->toBeNull();
});

it('equals the same BSB written differently, and no other', function (): void {
    $bsb = Bsb::from('062-000');

    expect($bsb->equals(Bsb::from('062000')))->toBeTrue()
        ->and($bsb->equals(Bsb::from('123-456')))->toBeFalse();
});

it('casts and JSON-encodes to its digits, keeping the leading zero', function (): void {
    $bsb = Bsb::from('062-000');

    expect((string) $bsb)->toBe('062000')
        ->and(json_encode(['bsb' => $bsb]))->toBe('{"bsb":"062000"}');
});

it('can only be created through its named constructors', function (): void {
    expect(fn () => new Bsb('062000'))->toThrow(Error::class, 'Call to private');
});
