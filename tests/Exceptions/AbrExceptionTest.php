<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Exceptions\MissingGuidException;

mutates(AbrException::class, MissingGuidException::class);

describe('AbrException', function (): void {
    it('is a runtime exception that can be extended', function (): void {
        $class = new ReflectionClass(AbrException::class);

        expect($class->isSubclassOf(RuntimeException::class))->toBeTrue()
            ->and($class->isFinal())->toBeFalse();
    });

    it('describes the failure without a GUID or URL', function (AbrException $exception, string $message): void {
        expect($exception)->toBeInstanceOf(RuntimeException::class)
            ->and($exception->getMessage())->toBe($message);

        expectNoGuidOrUrl($exception->getMessage());
    })->with([
        'connection failed' => [
            fn (): AbrException => AbrException::connectionFailed(),
            'Could not connect to ABN Lookup.',
        ],
        'unexpected status' => [
            fn (): AbrException => AbrException::unexpectedStatus(503),
            'ABN Lookup responded with HTTP status 503.',
        ],
        'malformed response' => [
            fn (): AbrException => AbrException::malformedResponse(),
            'ABN Lookup returned a response that could not be read.',
        ],
        'service error' => [
            fn (): AbrException => AbrException::serviceError('The GUID entered is not recognised as a Registered Party'),
            'ABN Lookup returned an error: The GUID entered is not recognised as a Registered Party',
        ],
    ]);
});

describe('MissingGuidException', function (): void {
    it('is a final ABR exception that explains how to set ABR_GUID', function (): void {
        $exception = new MissingGuidException;

        expect($exception)->toBeInstanceOf(AbrException::class)
            ->and((new ReflectionClass(MissingGuidException::class))->isFinal())->toBeTrue()
            ->and($exception->getMessage())->toContain('ABR_GUID')->toMatch('/\.env|environment/i');

        expectNoGuidOrUrl($exception->getMessage());
    });
});
