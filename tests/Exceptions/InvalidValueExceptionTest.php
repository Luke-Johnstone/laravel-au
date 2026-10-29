<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;

mutates(InvalidValueException::class);

it('creates an invalid argument exception with a fixed message that takes no input', function (string $constructor, string $message): void {
    $exception = InvalidValueException::$constructor();

    expect($exception)->toBeInstanceOf(InvalidArgumentException::class)
        ->and($exception->getMessage())->toBe($message)
        ->and((new ReflectionMethod(InvalidValueException::class, $constructor))->getNumberOfParameters())->toBe(0);
})->with([
    'ABN' => ['abn', 'The given value is not a valid ABN.'],
    'ACN' => ['acn', 'The given value is not a valid ACN.'],
    'BSB' => ['bsb', 'The given value is not a valid BSB.'],
    'phone number' => ['phone', 'The given value is not a valid phone number.'],
    'postcode' => ['postcode', 'The given value is not a valid postcode.'],
]);
