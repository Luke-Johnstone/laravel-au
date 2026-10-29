<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Digits;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;

mutates(Postcode::class, Digits::class);

// Input, value, state. One per state; some also cover trimming surrounding whitespace.
dataset('valid postcodes', [
    'NSW' => ['2000', '2000', State::NSW],
    'VIC, with surrounding spaces' => ['  3000  ', '3000', State::VIC],
    'QLD' => ['4000', '4000', State::QLD],
    'SA' => ['5000', '5000', State::SA],
    'WA' => ['6000', '6000', State::WA],
    'TAS' => ['7000', '7000', State::TAS],
    'NT, keeping the leading zero, with a surrounding tab and newline' => ["\t0800\n", '0800', State::NT],
    'ACT, with surrounding non-breaking spaces (U+00A0)' => ["\u{00A0}2600\u{00A0}", '2600', State::ACT],
]);

dataset('invalid postcodes', [
    'three digits, without the leading zero' => '800',
    'five digits' => '20000',
    'in no state (0100)' => '0100',
    'in no state (0500)' => '0500',
    'a letter in place of a digit' => '20a0',
    'an internal space' => '20 00',
    'an internal non-breaking space (U+00A0)' => "20\u{00A0}00",
]);

dataset('blank postcodes', [
    'an empty string' => '',
    'spaces only' => '    ',
    'a tab and newline only' => "\t\n",
    'non-breaking spaces only' => "\u{00A0}\u{00A0}",
]);

it('accepts a postcode inside a state range', function (string $input, string $value, State $state): void {
    $postcode = Postcode::from($input);

    expect(Postcode::isValid($input))->toBeTrue()
        ->and(Postcode::tryFrom($input)?->value)->toBe($value)
        ->and($postcode->value)->toBe($value)
        ->and($postcode->format())->toBe($value)
        ->and($postcode->state())->toBe($state)
        ->and(array_values(array_filter(State::cases(), $postcode->belongsTo(...))))->toBe([$state]);
})->with('valid postcodes');

it('rejects an invalid postcode without revealing it', function (string $input): void {
    expect(Postcode::isValid($input))->toBeFalse()
        ->and(Postcode::tryFrom($input))->toBeNull()
        ->and(fn () => Postcode::from($input))->toThrow(function (InvalidValueException $exception) use ($input): void {
            expect($exception->getMessage())
                ->toBe('The given value is not a valid postcode.')
                ->not->toContain($input)
                ->not->toMatch('/\d/');
        });
})->with('invalid postcodes');

it('rejects blank input', function (string $input): void {
    expect(Postcode::isValid($input))->toBeFalse()
        ->and(Postcode::tryFrom($input))->toBeNull()
        ->and(fn () => Postcode::from($input))->toThrow(InvalidValueException::class, 'The given value is not a valid postcode.');
})->with('blank postcodes');

it('treats null as invalid', function (): void {
    expect(Postcode::isValid(null))->toBeFalse()
        ->and(Postcode::tryFrom(null))->toBeNull();
});

it('equals the same postcode with surrounding whitespace, and no other', function (): void {
    $postcode = Postcode::from('2600');

    expect($postcode->equals(Postcode::from(' 2600 ')))->toBeTrue()
        ->and($postcode->equals(Postcode::from('2601')))->toBeFalse();
});

it('casts and JSON-encodes to its four digits, keeping the leading zero', function (): void {
    $postcode = Postcode::from(' 0800 ');

    expect((string) $postcode)->toBe('0800')
        ->and(json_encode(['postcode' => $postcode]))->toBe('{"postcode":"0800"}');
});

it('can only be created through its named constructors', function (): void {
    expect(fn () => new Postcode('2000'))->toThrow(Error::class, 'Call to private');
});
