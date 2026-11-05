<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Abr;

use EastpointLabs\LaravelAu\Abr\Contracts\LooksUpAbns;
use EastpointLabs\LaravelAu\Abr\Data\AbnDetails;
use EastpointLabs\LaravelAu\Abr\Data\NameMatch;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Exceptions\MissingGuidException;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\HttpClientException;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Calls the ABN Lookup JSON web services.
 *
 * Input is checked before any request is sent, successful ABN and ACN lookups are cached, and
 * the GUID never reaches an exception message, a stack trace or a cache key. Failed requests
 * aren't retried.
 */
final readonly class AbrClient implements LooksUpAbns
{
    /**
     * The most name matches ABN Lookup returns for one search.
     */
    public const int MAX_RESULTS = 200;

    public function __construct(
        private Factory $http,
        private Repository $cache,
        #[SensitiveParameter] private ?string $guid,
        private string $baseUrl,
        private int $timeout = 10,
        private bool $cacheEnabled = true,
        private int $cacheTtl = 86400,
    ) {}

    public function abn(Abn|string $abn): ?AbnDetails
    {
        $abn = is_string($abn) ? Abn::from($abn) : $abn;

        return $this->details('AbnDetails.aspx', 'abn', $abn->value);
    }

    public function acn(Acn|string $acn): ?AbnDetails
    {
        $acn = is_string($acn) ? Acn::from($acn) : $acn;

        return $this->details('AcnDetails.aspx', 'acn', $acn->value);
    }

    public function searchByName(string $name, int $maxResults = 10): array
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('The name to search ABN Lookup for must not be empty.');
        }

        $guid = $this->guid();

        $payload = $this->request('MatchingNames.aspx', [
            'name' => $name,
            'maxResults' => max(1, min(self::MAX_RESULTS, $maxResults)),
        ], $guid);

        $names = $payload['Names'] ?? null;

        if (! is_array($names) || $names === []) {
            self::ensureNotAnError($payload, $guid);
        }

        return NameMatch::listFromPayload($payload);
    }

    /**
     * Look up one ABN or ACN, using the cache when it's enabled. The decoded payload is cached
     * rather than the object, so cached entries are plain arrays any cache store can hold.
     */
    private function details(string $endpoint, string $parameter, string $digits): ?AbnDetails
    {
        $guid = $this->guid();
        $key = "laravel-au:abr:{$parameter}:{$digits}";

        if ($this->cacheEnabled) {
            $cached = $this->cache->get($key);

            if (is_array($cached)) {
                return AbnDetails::fromPayload($cached);
            }
        }

        $payload = $this->request($endpoint, [$parameter => $digits], $guid);

        $abn = $payload['Abn'] ?? null;

        if (! is_string($abn) || trim($abn) === '') {
            self::ensureNotAnError($payload, $guid);

            return null;
        }

        $details = AbnDetails::fromPayload($payload);

        if ($this->cacheEnabled) {
            $this->cache->put($key, $payload, $this->cacheTtl);
        }

        return $details;
    }

    /**
     * Send a GET request and decode the JSONP response.
     *
     * @param  array<string, int|string>  $query
     * @return array<array-key, mixed>
     *
     * @throws AbrException
     */
    private function request(string $endpoint, array $query, #[SensitiveParameter] string $guid): array
    {
        try {
            $response = $this->http
                ->timeout($this->timeout)
                ->get(rtrim($this->baseUrl, '/').'/'.$endpoint, [
                    ...$query,
                    'callback' => 'callback',
                    'guid' => $guid,
                ]);
        } catch (HttpClientException|TransferException) {
            // Not chained as the previous exception: its message includes the URL, and the URL
            // carries the GUID. Older Laravel versions wrap only Guzzle's connection failures and
            // let its other errors through as they are.
            throw AbrException::connectionFailed();
        }

        if (! $response->successful()) {
            throw AbrException::unexpectedStatus($response->status());
        }

        return JsonpDecoder::decode($response->body());
    }

    /**
     * A response with no data means either "not found" or an error the service is reporting,
     * such as an unrecognised GUID. Only the message tells them apart.
     *
     * @param  array<array-key, mixed>  $payload
     *
     * @throws AbrException when the message reports anything other than no records found
     */
    private static function ensureNotAnError(array $payload, #[SensitiveParameter] string $guid): void
    {
        $message = $payload['Message'] ?? null;
        $message = is_string($message) ? trim($message) : '';

        if ($message !== '' && stripos($message, 'no records found') === false) {
            // The message comes from the service, so make sure it can't repeat the GUID.
            throw AbrException::serviceError(str_ireplace($guid, '[GUID]', $message));
        }
    }

    /**
     * @throws MissingGuidException
     */
    private function guid(): string
    {
        $guid = trim($this->guid ?? '');

        return $guid !== '' ? $guid : throw new MissingGuidException;
    }
}
