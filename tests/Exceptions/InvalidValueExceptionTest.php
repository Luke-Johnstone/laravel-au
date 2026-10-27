<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;

mutates(InvalidValueException::class);

dataset('invalid value constructors', [
    'ABN' => ['abn', 'The given value is not a valid ABN.'],
    'ACN' => ['acn', 'The given value is not a valid ACN.'],
    'BSB' => ['bsb', 'The given value is not a valid BSB.'],
    'phone number' => ['phone', 'The given value is not a valid phone number.'],
    'postcode' => ['postcode', 'The given value is not a valid postcode.'],
]);

it('creates an exception with a fixed message', function (string $constructor, string $message): void {
    $exception = InvalidValueException::$constructor();

    expect($exception)->toBeInstanceOf(InvalidValueException::class)
        ->and($exception)->toBeInstanceOf(InvalidArgumentException::class)
        ->and($exception->getMessage())->toBe($message);
})->with('invalid value constructors');

it('takes no arguments, so the input can never reach the message', function (string $constructor, string $message): void {
    $method = new ReflectionMethod(InvalidValueException::class, $constructor);

    expect($method->isPublic())->toBeTrue()
        ->and($method->isStatic())->toBeTrue()
        ->and($method->getNumberOfParameters())->toBe(0)
        ->and(InvalidValueException::$constructor()->getMessage())->toBe($message);
})->with('invalid value constructors');

it('can be caught as an invalid argument exception', function (string $constructor): void {
    expect(fn () => throw InvalidValueException::$constructor())
        ->toThrow(InvalidArgumentException::class);
})->with([
    'ABN' => 'abn',
    'ACN' => 'acn',
    'BSB' => 'bsb',
    'phone number' => 'phone',
    'postcode' => 'postcode',
]);
