<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Exceptions;

use RuntimeException;

/**
 * Thrown when ABN Lookup can't be reached or doesn't answer as expected.
 *
 * Messages are written here rather than passed through from the HTTP client, because the
 * client's messages include the request URL, and the URL carries the GUID.
 */
class AbrException extends RuntimeException
{
    public static function connectionFailed(): self
    {
        return new self('Could not connect to ABN Lookup.');
    }

    public static function unexpectedStatus(int $status): self
    {
        return new self("ABN Lookup responded with HTTP status {$status}.");
    }

    public static function malformedResponse(): self
    {
        return new self('ABN Lookup returned a response that could not be read.');
    }

    /**
     * An error reported by ABN Lookup itself, such as an unrecognised GUID.
     */
    public static function serviceError(string $message): self
    {
        return new self("ABN Lookup returned an error: {$message}");
    }
}
