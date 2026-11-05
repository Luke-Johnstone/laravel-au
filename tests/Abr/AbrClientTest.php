<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\AbrClient;
use EastpointLabs\LaravelAu\Abr\Contracts\LooksUpAbns;
use EastpointLabs\LaravelAu\Abr\Data\AbnDetails;
use EastpointLabs\LaravelAu\Abr\Data\NameMatch;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Exceptions\MissingGuidException;
use EastpointLabs\LaravelAu\LaravelAuServiceProvider;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

mutates(AbrClient::class, LaravelAuServiceProvider::class);

describe('container binding', function (): void {
    it('shares one client between the contract and the class', function (): void {
        $client = app(LooksUpAbns::class);

        expect($client)->toBeInstanceOf(AbrClient::class)
            ->and(app(AbrClient::class))->toBe($client)
            ->and(app(LooksUpAbns::class))->toBe($client);
    });

    it('replaces only the contract when another implementation is bound to it', function (): void {
        $client = app(LooksUpAbns::class);
        $replacement = Mockery::mock(LooksUpAbns::class);

        expect(app()->isAlias(LooksUpAbns::class))->toBeFalse();

        app()->instance(LooksUpAbns::class, $replacement);

        expect(app(LooksUpAbns::class))->toBe($replacement)
            ->and(app(AbrClient::class))->toBe($client);
    });
});

describe('abn()', function (): void {
    it('looks up an ABN and caches the details', function (Abn|string $abn): void {
        Http::fake(['abr.business.gov.au/json/AbnDetails.aspx*' => Http::response(fixtureContents('abr/abn-active.jsonp'))]);
        $expected = AbnDetails::fromPayload(abrPayload('abn-active.jsonp'))->toArray();
        $client = app(LooksUpAbns::class);

        $details = $client->abn($abn);

        expect($details)->toBeInstanceOf(AbnDetails::class)
            ->and($details?->toArray())->toBe($expected)
            ->and(Cache::has('laravel-au:abr:abn:51824753556'))->toBeTrue();

        expectOneGetRequest('https://abr.business.gov.au/json/AbnDetails.aspx', [
            'abn' => '51824753556',
            'callback' => 'callback',
            'guid' => 'testing-guid',
        ]);

        expect($client->abn($abn)?->toArray())->toBe($expected);

        Http::assertSentCount(1);
    })->with([
        'digits' => '51824753556',
        'a formatted ABN' => '51 824 753 556',
        'an Abn' => Abn::from('51824753556'),
    ]);

    it('trims a trailing slash from the base URL', function (): void {
        config(['au.abr.base_url' => 'https://abr.example.test/json/']);
        Http::fake(['abr.example.test/*' => Http::response(fixtureContents('abr/abn-active.jsonp'))]);

        expect(app(LooksUpAbns::class)->abn('51824753556'))->toBeInstanceOf(AbnDetails::class);

        expectOneGetRequest('https://abr.example.test/json/AbnDetails.aspx', [
            'abn' => '51824753556',
            'callback' => 'callback',
            'guid' => 'testing-guid',
        ]);
    });

    it('returns a suppressed ABN as details and caches them', function (): void {
        Http::fake(['*' => Http::response(fixtureContents('abr/abn-suppressed.jsonp'))]);
        $client = app(LooksUpAbns::class);

        $details = $client->abn('83 914 571 673');

        expect($details)->toBeInstanceOf(AbnDetails::class)
            ->and($details?->abn->value)->toBe('83914571673')
            ->and($details?->isSuppressed())->toBeTrue()
            ->and(Cache::has('laravel-au:abr:abn:83914571673'))->toBeTrue()
            ->and($client->abn('83914571673')?->isSuppressed())->toBeTrue();

        Http::assertSentCount(1);
    });
});

describe('acn()', function (): void {
    it('looks up an ACN and caches the details', function (Acn|string $acn): void {
        Http::fake(['abr.business.gov.au/json/AcnDetails.aspx*' => Http::response(fixtureContents('abr/acn-active.jsonp'))]);
        $expected = AbnDetails::fromPayload(abrPayload('acn-active.jsonp'))->toArray();
        $client = app(LooksUpAbns::class);

        $details = $client->acn($acn);

        expect($details)->toBeInstanceOf(AbnDetails::class)
            ->and($details?->toArray())->toBe($expected)
            ->and(Cache::has('laravel-au:abr:acn:004085616'))->toBeTrue();

        expectOneGetRequest('https://abr.business.gov.au/json/AcnDetails.aspx', [
            'acn' => '004085616',
            'callback' => 'callback',
            'guid' => 'testing-guid',
        ]);

        expect($client->acn($acn)?->toArray())->toBe($expected);

        Http::assertSentCount(1);
    })->with([
        'digits' => '004085616',
        'a formatted ACN' => '004 085 616',
        'an Acn' => Acn::from('004085616'),
    ]);
});

describe('searchByName()', function (): void {
    it('searches by the trimmed name and returns the matches in order', function (): void {
        Http::fake(['abr.business.gov.au/json/MatchingNames.aspx*' => Http::response(fixtureContents('abr/names-search.jsonp'))]);

        $matches = app(LooksUpAbns::class)->searchByName("  Example Holdings \t");

        expect($matches)->toBeList()
            ->toHaveCount(3)
            ->each->toBeInstanceOf(NameMatch::class)
            ->and(array_map(fn (NameMatch $match): string => $match->abn->value, $matches))
            ->toBe(['51824753556', '53004085616', '83914571673'])
            ->and($matches)->toEqual(NameMatch::listFromPayload(abrPayload('names-search.jsonp')));

        expectOneGetRequest('https://abr.business.gov.au/json/MatchingNames.aspx', [
            'name' => 'Example Holdings',
            'maxResults' => '10',
            'callback' => 'callback',
            'guid' => 'testing-guid',
        ]);
    });

    it('clamps maxResults to between 1 and 200', function (int $maxResults, string $sent): void {
        Http::fake(['*' => Http::response(fixtureContents('abr/names-search.jsonp'))]);

        app(LooksUpAbns::class)->searchByName('Example', $maxResults);

        expectOneGetRequest('https://abr.business.gov.au/json/MatchingNames.aspx', [
            'name' => 'Example',
            'maxResults' => $sent,
            'callback' => 'callback',
            'guid' => 'testing-guid',
        ]);
    })->with([
        'zero' => [0, '1'],
        'a negative number' => [-5, '1'],
        'the maximum' => [200, '200'],
        'more than the maximum' => [201, '200'],
    ]);

    it('returns an empty list when nothing matches', function (string $message): void {
        Http::fake(['*' => Http::response('callback('.json_encode(['Message' => $message, 'Names' => []]).')')]);

        expect(app(LooksUpAbns::class)->searchByName('Example Holdings'))->toBe([]);
    })->with([
        'no message' => '',
        'a not-found message' => 'No records found',
    ]);

    it('throws the error ABN Lookup reports', function (): void {
        $error = 'The GUID entered is not recognised as a Registered Party';
        Http::fake(['*' => Http::response('callback('.json_encode(['Message' => $error, 'Names' => []]).')')]);

        expect(fn () => app(LooksUpAbns::class)->searchByName('Example Holdings'))
            ->toThrow(function (AbrException $exception) use ($error): void {
                expect($exception->getMessage())->toBe("ABN Lookup returned an error: {$error}");

                expectNoGuidOrUrl($exception->getMessage());
            });
    });

    it('never caches a search', function (): void {
        Http::fake(['*' => Http::response(fixtureContents('abr/names-search.jsonp'))]);
        $client = app(LooksUpAbns::class);

        expect($client->searchByName('Example Holdings'))->toHaveCount(3)
            ->and($client->searchByName('Example Holdings'))->toHaveCount(3);

        Http::assertSentCount(2);
    });

    it('rejects an empty name without sending a request', function (string $name): void {
        Http::fake(['*' => Http::response(fixtureContents('abr/names-search.jsonp'))]);

        expect(fn () => app(LooksUpAbns::class)->searchByName($name))
            ->toThrow(new InvalidArgumentException('The name to search ABN Lookup for must not be empty.'));

        Http::assertNothingSent();
    })->with([
        'an empty string' => '',
        'whitespace only' => " \t\n ",
    ]);
});

describe('records that are not found', function (): void {
    it('returns null and caches nothing', function (string $method, string $number, string $body): void {
        Http::fake(['*' => Http::response($body)]);
        $client = app(LooksUpAbns::class);

        expect($client->{$method}($number))->toBeNull()
            ->and(Cache::has("laravel-au:abr:{$method}:{$number}"))->toBeFalse()
            ->and($client->{$method}($number))->toBeNull();

        Http::assertSentCount(2);
    })->with([
        'the not-found response' => ['abn', '51824753556', fn (): string => fixtureContents('abr/abn-not-found.jsonp')],
        'an empty message' => ['abn', '51824753556', 'callback({"Abn":"","Message":""})'],
        'a not-found message in capitals' => ['abn', '51824753556', 'callback({"Abn":"","Message":"NO RECORDS FOUND."})'],
        'a blank ABN' => ['abn', '51824753556', 'callback({"Abn":"   ","AbnStatus":"","Message":""})'],
        'a blank message' => ['abn', '51824753556', 'callback({"Abn":"","Message":"   "})'],
        'an ACN with no record' => ['acn', '004085616', fn (): string => fixtureContents('abr/abn-not-found.jsonp')],
    ]);
});

describe('when ABN Lookup fails', function (): void {
    // Keep arguments in stack traces, as development setups do, so the traces can be checked.
    beforeEach(fn () => $this->ignoreArgs = ini_set('zend.exception_ignore_args', '0'));
    afterEach(fn () => ini_set('zend.exception_ignore_args', (string) $this->ignoreArgs));

    it('throws an exception that gives nothing away and caches nothing', function (Closure $respond, string $message): void {
        Http::fake(['*' => $respond()]);
        $client = app(LooksUpAbns::class);

        expect(fn () => $client->abn('51824753556'))->toThrow(function (AbrException $exception) use ($message): void {
            expect($exception->getMessage())->toBe($message)
                ->and($exception->getPrevious())->toBeNull();

            expectNoGuidOrUrl($exception->getMessage());
            expectNoGuidInTrace($exception);
        });

        Http::assertSentCount(1);

        expect(Cache::has('laravel-au:abr:abn:51824753556'))->toBeFalse()
            ->and(fn () => $client->abn('51824753556'))->toThrow(AbrException::class, $message);

        Http::assertSentCount(2);
    })->with([
        'a connection failure' => [
            fn (): Closure => Http::failedConnection(),
            'Could not connect to ABN Lookup.',
        ],
        'HTTP status 500' => [
            fn (): PromiseInterface => Http::response('<html><body>Error for https://abr.business.gov.au/json/AbnDetails.aspx?guid=testing-guid</body></html>', 500),
            'ABN Lookup responded with HTTP status 500.',
        ],
        'HTTP status 404' => [
            fn (): PromiseInterface => Http::response('Not Found', 404),
            'ABN Lookup responded with HTTP status 404.',
        ],
        'an HTML page' => [
            fn (): PromiseInterface => Http::response('<!DOCTYPE html><html><body>Service unavailable for guid=testing-guid</body></html>'),
            'ABN Lookup returned a response that could not be read.',
        ],
        'details with an invalid ABN' => [
            fn (): PromiseInterface => Http::response('callback({"Abn":"123","AbnStatus":"Active"})'),
            'ABN Lookup returned a response that could not be read.',
        ],
        'an error reported by ABN Lookup' => [
            fn (): PromiseInterface => Http::response(fixtureContents('abr/error.jsonp')),
            'ABN Lookup returned an error: The GUID entered is not recognised as a Registered Party',
        ],
    ]);

    it('hides an error the HTTP library throws unwrapped', function (): void {
        Http::fake(['*' => fn (Request $request): PromiseInterface => Create::rejectionFor(
            new RequestException("cURL error 56: Recv failure for {$request->url()}", $request->toPsrRequest()),
        )]);

        expect(fn () => app(LooksUpAbns::class)->abn('51824753556'))->toThrow(function (AbrException $exception): void {
            expect($exception->getMessage())->toBe('Could not connect to ABN Lookup.')
                ->and($exception->getPrevious())->toBeNull();

            expectNoGuidOrUrl($exception->getMessage());
            expectNoGuidInTrace($exception);
        });
    });

    it('removes the GUID from an error that repeats it', function (string $error): void {
        Http::fake(['*' => Http::response('callback('.json_encode(['Abn' => '', 'Message' => $error]).')')]);

        expect(fn () => app(LooksUpAbns::class)->abn('51824753556'))->toThrow(function (AbrException $exception): void {
            expect($exception->getMessage())
                ->toStartWith('ABN Lookup returned an error: The GUID ')
                ->toEndWith(' is not recognised')
                ->and(mb_strtolower($exception->getMessage()))->not->toContain('testing-guid');

            expectNoGuidOrUrl($exception->getMessage());
        });
    })->with([
        'as configured' => 'The GUID testing-guid is not recognised',
        'in capitals' => 'The GUID TESTING-GUID is not recognised',
    ]);
});

describe('input checks', function (): void {
    it('rejects an invalid number without sending a request', function (string $method, string $number, string $message): void {
        Http::fake(['*' => Http::response(fixtureContents('abr/abn-active.jsonp'))]);

        expect(fn () => app(LooksUpAbns::class)->{$method}($number))
            ->toThrow(function (InvalidValueException $exception) use ($number, $message): void {
                expect($exception->getMessage())->toBe($message)
                    ->not->toContain($number)
                    ->not->toContain(str_replace(' ', '', $number));
            });

        Http::assertNothingSent();
    })->with([
        'an ABN with a bad checksum' => ['abn', '51 824 753 557', 'The given value is not a valid ABN.'],
        'an ACN with a bad checksum' => ['acn', '000 000 018', 'The given value is not a valid ACN.'],
    ]);

    it('resolves the client without a GUID but throws on every lookup', function (?string $guid): void {
        config(['au.abr.guid' => $guid]);
        Http::fake(['*' => Http::response(fixtureContents('abr/abn-active.jsonp'))]);

        $client = app(LooksUpAbns::class);

        expect($client)->toBeInstanceOf(AbrClient::class)
            ->and(fn () => $client->abn('51824753556'))->toThrow(MissingGuidException::class)
            ->and(fn () => $client->acn('004085616'))->toThrow(MissingGuidException::class)
            ->and(fn () => $client->searchByName('Example Holdings'))->toThrow(MissingGuidException::class);

        Http::assertNothingSent();
    })->with([
        'no GUID' => null,
        'an empty GUID' => '',
        'a blank GUID' => '   ',
    ]);

    it('checks the input before the GUID', function (): void {
        config(['au.abr.guid' => null]);
        $client = app(LooksUpAbns::class);

        expect(fn () => $client->abn('51 824 753 557'))->toThrow(InvalidValueException::abn())
            ->and(fn () => $client->acn('000 000 018'))->toThrow(InvalidValueException::acn())
            ->and(fn () => $client->searchByName(' '))
            ->toThrow(new InvalidArgumentException('The name to search ABN Lookup for must not be empty.'));
    });

    it('checks the GUID before the cache', function (): void {
        config(['au.abr.guid' => null]);
        Http::fake(['*' => Http::response(fixtureContents('abr/abn-active.jsonp'))]);
        Cache::put('laravel-au:abr:abn:51824753556', abrPayload('abn-active.jsonp'), 3600);

        expect(fn () => app(LooksUpAbns::class)->abn('51824753556'))->toThrow(MissingGuidException::class);

        Http::assertNothingSent();
    });
});

describe('caching', function (): void {
    it('keeps details for the configured TTL', function (): void {
        config(['au.abr.cache.ttl' => 60]);
        Http::fake(['*' => Http::response(fixtureContents('abr/abn-active.jsonp'))]);
        $this->freezeTime();
        $client = app(LooksUpAbns::class);

        $client->abn('51824753556');
        $this->travel(59)->seconds();
        $client->abn('51824753556');

        Http::assertSentCount(1);

        $this->travel(1)->seconds();

        expect($client->abn('51824753556'))->toBeInstanceOf(AbnDetails::class);

        Http::assertSentCount(2);
    });

    it('neither reads nor writes the cache when caching is disabled', function (): void {
        config(['au.abr.cache.enabled' => false]);
        Http::fake(['*' => Http::response(fixtureContents('abr/abn-active.jsonp'))]);
        Cache::put('laravel-au:abr:acn:004085616', abrPayload('acn-active.jsonp'), 3600);
        $client = app(LooksUpAbns::class);

        expect($client->acn('004085616'))->toBeInstanceOf(AbnDetails::class)
            ->and($client->abn('51824753556'))->toBeInstanceOf(AbnDetails::class)
            ->and($client->abn('51824753556'))->toBeInstanceOf(AbnDetails::class)
            ->and(Cache::has('laravel-au:abr:abn:51824753556'))->toBeFalse();

        Http::assertSentCount(3);
    });

    it('uses the configured cache store', function (): void {
        config([
            'cache.stores.abr' => ['driver' => 'array'],
            'au.abr.cache.store' => 'abr',
        ]);
        Http::fake(['*' => Http::response(fixtureContents('abr/abn-active.jsonp'))]);
        $client = app(LooksUpAbns::class);

        $client->abn('51824753556');
        $client->abn('51824753556');

        expect(Cache::store('abr')->has('laravel-au:abr:abn:51824753556'))->toBeTrue()
            ->and(Cache::has('laravel-au:abr:abn:51824753556'))->toBeFalse();

        Http::assertSentCount(1);
    });
});

it('applies the timeout from the config', function (): void {
    config(['au.abr.timeout' => 3]);
    $timeout = null;

    Http::fake(['*' => function (Request $request, array $options) use (&$timeout): PromiseInterface {
        $timeout = $options['timeout'] ?? null;

        return Http::response(fixtureContents('abr/abn-active.jsonp'));
    }]);

    app(LooksUpAbns::class)->abn('51824753556');

    expect($timeout)->toEqual(3);
});
