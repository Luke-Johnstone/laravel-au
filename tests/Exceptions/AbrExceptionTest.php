<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Exceptions\MissingGuidException;

mutates(AbrException::class, MissingGuidException::class);

dataset('abr exceptions', [
    'connection failed' => fn (): AbrException => AbrException::connectionFailed(),
    'unexpected status' => fn (): AbrException => AbrException::unexpectedStatus(503),
    'malformed response' => fn (): AbrException => AbrException::malformedResponse(),
    'service error' => fn (): AbrException => AbrException::serviceError('The GUID entered is not recognised as a Registered Party'),
    'missing GUID' => fn (): AbrException => new MissingGuidException,
]);

describe('AbrException', function (): void {
    it('is a runtime exception that can be extended', function (): void {
        $class = new ReflectionClass(AbrException::class);

        expect($class->isSubclassOf(RuntimeException::class))->toBeTrue()
            ->and($class->isFinal())->toBeFalse();
    });

    it('creates an exception for each failure', function (AbrException $exception): void {
        expect($exception)->toBeInstanceOf(AbrException::class)
            ->and($exception)->toBeInstanceOf(RuntimeException::class)
            ->and($exception->getMessage())->not->toBe('');
    })->with('abr exceptions');

    it('mentions the HTTP status in an unexpected status message', function (int $status): void {
        expect(AbrException::unexpectedStatus($status)->getMessage())->toContain((string) $status);
    })->with([
        'server error' => 500,
        'service unavailable' => 503,
        'not found' => 404,
    ]);

    it("includes the service's own message in a service error", function (): void {
        $message = 'The GUID entered is not recognised as a Registered Party';

        expect(AbrException::serviceError($message)->getMessage())->toContain($message);
    });

    it('gives each failure its own message', function (): void {
        $messages = [
            AbrException::connectionFailed()->getMessage(),
            AbrException::unexpectedStatus(503)->getMessage(),
            AbrException::malformedResponse()->getMessage(),
            AbrException::serviceError('No records found')->getMessage(),
        ];

        expect(array_unique($messages))->toHaveCount(4);
    });

    it('never puts a GUID or URL in the message', function (AbrException $exception): void {
        expectNoGuidOrUrl($exception->getMessage());
    })->with('abr exceptions');

    it('can be caught as a runtime exception', function (): void {
        expect(fn () => throw AbrException::connectionFailed())->toThrow(RuntimeException::class);
    });
});

describe('MissingGuidException', function (): void {
    it('is a final ABR exception', function (): void {
        $class = new ReflectionClass(MissingGuidException::class);

        expect($class->isSubclassOf(AbrException::class))->toBeTrue()
            ->and($class->isFinal())->toBeTrue();
    });

    it('explains how to set ABR_GUID by default', function (): void {
        $message = (new MissingGuidException)->getMessage();

        expect($message)
            ->toContain('ABR_GUID')
            ->toMatch('/\.env|environment/i');
    });

    it('does not contain a GUID', function (): void {
        expectNoGuidOrUrl((new MissingGuidException)->getMessage());
    });

    it('can be caught as an ABR exception', function (): void {
        expect(fn () => throw new MissingGuidException)->toThrow(AbrException::class);
    });
});
