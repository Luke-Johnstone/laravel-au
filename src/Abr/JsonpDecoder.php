<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Abr;

use EastpointLabs\LaravelAu\Exceptions\AbrException;
use JsonException;
use SensitiveParameter;

/**
 * Decodes ABN Lookup's JSONP responses, which wrap the JSON in a callback: `callback({...})`.
 *
 * @internal
 */
final readonly class JsonpDecoder
{
    private function __construct() {}

    /**
     * Strip a single `name(...)` wrapper, with an optional trailing semicolon, and decode the
     * JSON inside. Plain JSON is accepted too. The body is hidden from stack traces, because an
     * error page can repeat the request URL, GUID included.
     *
     * @return array<array-key, mixed>
     *
     * @throws AbrException when the body isn't a JSON object or array
     */
    public static function decode(#[SensitiveParameter] string $body): array
    {
        $json = trim($body);

        if (preg_match('/\A[A-Za-z_$][\w$.]*\s*\((.*)\)\s*;?\z/s', $json, $matches) === 1) {
            $json = $matches[1];
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw AbrException::malformedResponse();
        }

        if (! is_array($data)) {
            throw AbrException::malformedResponse();
        }

        return $data;
    }
}
