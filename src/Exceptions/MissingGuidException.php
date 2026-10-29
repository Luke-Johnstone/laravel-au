<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Exceptions;

/**
 * Thrown when an ABN Lookup method is called but no GUID is configured. It's thrown on use, not
 * at boot, so apps that only use the validation rules don't need a GUID.
 */
final class MissingGuidException extends AbrException
{
    public function __construct(
        string $message = 'No ABN Lookup GUID is set. Request a free GUID from the Australian Business Register and add it to your .env file as ABR_GUID.',
    ) {
        parent::__construct($message);
    }
}
