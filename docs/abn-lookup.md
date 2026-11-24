# ABN Lookup

This page is part of the [laravel-au](../README.md) docs. For setup, see
[Installation](../README.md#installation).

`Abr` looks up businesses on the Australian Business Register through its free ABN Lookup web
services. It needs the `ABR_GUID` from [Installation](../README.md#installation), and so does
the [active ABN rule](validation.md#active-abn-rule). The other validation rules and the value
objects don't, so you can skip the GUID if those are all you use.

```php
use EastpointLabs\LaravelAu\Facades\Abr;

Abr::abn('51 824 753 556');            // ?AbnDetails
Abr::acn('004 085 616');               // ?AbnDetails for the company's ABN
Abr::searchByName('Example Holdings'); // list<NameMatch>

Abr::searchByName('Example Holdings', maxResults: 20);
```

`abn()` and `acn()` take a string in any form the value objects accept, or an `Abn` or `Acn`
object. `searchByName()` searches entity, business and trading names, and returns the matches
in the order ABN Lookup ranks them. `maxResults` defaults to 10 and is kept between 1 and 200
(`AbrClient::MAX_RESULTS`).

In application code, inject the `LooksUpAbns` contract instead of using the facade. It resolves
to the same client, and in tests `Abr::fake()` replaces it (see
[Testing your app](#testing-your-app)).

```php
use EastpointLabs\LaravelAu\Abr\Contracts\LooksUpAbns;

final class SupplierCheck
{
    public function __construct(private LooksUpAbns $abr) {}

    public function isActive(string $abn): bool
    {
        return $this->abr->abn($abn)?->isActive() ?? false;
    }
}
```

## Reading the results

`abn()` and `acn()` return `null` when the number isn't on the register, so check for it first:

```php
$details = Abr::abn('51 824 753 556');

if ($details !== null) {
    $details->displayName();     // ?string: the first business name, else the entity name
    $details->isActive();        // bool: the status is "Active"
    $details->isGstRegistered(); // bool
    $details->isSuppressed();    // bool
}
```

`AbnDetails` also holds the register's fields as properties: `abn`, `acn`, `status`,
`statusEffectiveFrom`, `entityName`, `entityTypeCode`, `entityTypeName`, `businessNames`,
`gstRegisteredFrom`, `postcode` and `state`. Numbers are value objects, dates are
`DateTimeImmutable` and the state is a `State`. `toArray()` returns them all as plain values.

An ABN holder can ask the register to suppress their details. A suppressed ABN still returns
`AbnDetails`, with its status and GST registration, but no names or address. `isSuppressed()`
is then `true` and `displayName()` returns `null`, so don't assume there's always a name to
show.

Each name search result is a `NameMatch`:

```php
foreach (Abr::searchByName('Example Holdings') as $match) {
    $match->abn;       // Abn
    $match->name;      // string: the name that matched
    $match->nameType;  // string, such as "Entity Name" or "Business Name"
    $match->state;     // ?State
    $match->postcode;  // ?Postcode
    $match->score;     // int: higher is a closer match
    $match->isCurrent; // bool
}
```

## Errors

`null` means the ABN or ACN wasn't found. Other problems throw:

- An invalid ABN or ACN throws `InvalidValueException`, and an empty name throws
  `InvalidArgumentException`. Both are thrown before any request is sent.
- `AbrException` means the lookup failed: the connection failed or timed out, ABN Lookup
  answered with an HTTP error status or a response that couldn't be read, or it reported an
  error of its own, such as an unrecognised GUID.
- `MissingGuidException` means no GUID is set. It's thrown when you call a lookup, not when the
  app boots. It extends `AbrException`, so catching `AbrException` handles both.

The package's exceptions live in `EastpointLabs\LaravelAu\Exceptions`. Failed requests aren't
retried. If you need retries, add them where you make the call, such as in a queued job.

## Caching and settings

Successful `abn()` and `acn()` lookups are cached for a day by default, so looking up the same
number again sends no request. Not-found results, errors and name searches aren't cached.

The trade-off is that a cached result can be out of date: an ABN cancelled after it was cached
still shows as active until the entry expires. Lower `ABR_CACHE_TTL`, or turn caching off,
where that matters.

To change the defaults, set these in your `.env` file:

| Setting             | Default                            | Purpose                                 |
|---------------------|------------------------------------|-----------------------------------------|
| `ABR_TIMEOUT`       | `10`                               | Request timeout, in seconds             |
| `ABR_CACHE_ENABLED` | `true`                             | Set to `false` to turn caching off      |
| `ABR_CACHE_STORE`   | your app's default store           | The cache store to use, such as `redis` |
| `ABR_CACHE_TTL`     | `86400` (one day)                  | How long to keep a lookup, in seconds   |
| `ABR_BASE_URL`      | `https://abr.business.gov.au/json` | The ABN Lookup address                  |

## Keeping the GUID private

The GUID never appears in the package's exception messages, stack trace arguments or cache
keys. ABN Lookup takes it as a query parameter, though, so anything that sees the request URL
sees the GUID. That includes tools that record outgoing HTTP requests, such as Laravel
Telescope, and the error Laravel throws for an unfaked request after
`Http::preventStrayRequests()`. In tests, use [`Abr::fake()`](#testing-your-app) so no request
is made.

## Testing your app

`Abr::fake()` replaces ABN Lookup with a fake for the rest of the test. The fake answers from
details you give it and never sends a request. It's bound to the `LooksUpAbns` contract, so
code that injects the contract gets it too, not just code that uses the facade.

This test checks the `SupplierCheck` class from above:

```php
use EastpointLabs\LaravelAu\Facades\Abr;

it('only accepts active suppliers', function (): void {
    Abr::fake([
        '51824753556' => ['EntityName' => 'Example Holdings Pty Ltd'],
        '83914571673' => ['AbnStatus' => 'Cancelled'],
    ]);

    $check = app(SupplierCheck::class);

    expect($check->isActive('51 824 753 556'))->toBeTrue()
        ->and($check->isActive('83 914 571 673'))->toBeFalse();

    Abr::assertLookedUp('51 824 753 556');
    Abr::assertLookupCount(2);
});
```

Each key is an ABN. Each value is an `AbnDetails` object, or an array of ABN Lookup fields,
such as `EntityName`, `AbnStatus` and `Acn`, merged over an active record for that ABN. `acn()`
finds configured details by their `Acn` field, so set `'Acn' => '004085616'` on
`53 004 085 616` to look that company up by its ACN.

An ABN or ACN you haven't configured returns `null`, and a name search returns no matches.
After `preventStrayLookups()`, an unconfigured ABN or ACN throws a `RuntimeException` instead,
so a test can't pass by accident. It isn't an `AbrException`, so code that handles ABN Lookup
errors won't hide it.

Input is checked like the real client checks it, so an invalid ABN or ACN still throws
`InvalidValueException`.

These methods set up other answers. Each returns the fake, so they chain:

- `notFound(...$abns)`: these ABNs return `null`, even after `preventStrayLookups()`.
- `suppressed($abn, $status = 'Active', $gst = false)`: this ABN returns suppressed details,
  with a status and GST registration but no names or address.
- `withNameMatches($matches)`: what `searchByName()` returns, whatever the name. Each match is
  a `NameMatch`, or an array with the keys `Abn`, `Name`, `NameType`, `State`, `Postcode`,
  `Score` and `IsCurrent`.
- `failing()`: every lookup throws `AbrException`, for testing your error handling.

```php
Abr::fake(['51824753556' => ['EntityName' => 'Example Holdings Pty Ltd']])
    ->suppressed('83 914 571 673', status: 'Cancelled')
    ->notFound('53 004 085 616')
    ->preventStrayLookups();
```

Assert on the lookups through the facade, or on the fake that `Abr::fake()` returns:

- `assertLookedUp($abn)` and `assertNotLookedUp($abn)`: whether `abn()` was called with this
  ABN. Only `abn()` calls count.
- `assertNothingLookedUp()`: no `abn()`, `acn()` or `searchByName()` call was made.
- `assertLookupCount($count)`: how many `abn()`, `acn()` and `searchByName()` calls were made in
  all, including repeats.
