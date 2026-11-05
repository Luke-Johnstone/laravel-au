# laravel-au

[![Tests](https://github.com/Luke-Johnstone/laravel-au/actions/workflows/ci.yml/badge.svg)](https://github.com/Luke-Johnstone/laravel-au/actions/workflows/ci.yml)
[![Latest version](https://img.shields.io/packagist/v/eastpointlabs/laravel-au.svg)](https://packagist.org/packages/eastpointlabs/laravel-au)
[![Licence](https://img.shields.io/packagist/l/eastpointlabs/laravel-au.svg)](LICENSE.md)

Australian business and address helpers for Laravel and Filament: ABN and ACN validation, ABN
Lookup, phone numbers, postcodes, states and BSBs.

> **Status:** in development. The API below is the planned v1 API and may change before the
> first release.

```php
use EastpointLabs\LaravelAu\Rules\Abn;
use EastpointLabs\LaravelAu\Rules\AuPhone;
use EastpointLabs\LaravelAu\Rules\AuPostcode;
use EastpointLabs\LaravelAu\Support\State;
use Illuminate\Validation\Rule;

$request->validate([
    'abn'      => ['required', new Abn],
    'phone'    => ['required', AuPhone::mobile()],
    'state'    => ['required', Rule::enum(State::class)],
    'postcode' => ['required', AuPostcode::matchingStateField('state')],
]);
```

## Why

Almost every Australian Laravel project ends up rewriting the same helpers: an ABN checksum, a
phone formatter, a postcode check. This package collects them in one well-tested place, with
optional Filament form fields on top.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 4 or 5 (optional, only for the form fields)

## Installation

```bash
composer require eastpointlabs/laravel-au
```

Publish the config file if you want to change the defaults, and the translations if you want to
change or translate the validation messages or Filament field labels:

```bash
php artisan vendor:publish --tag="au-config"
php artisan vendor:publish --tag="au-translations"
```

To use ABN Lookup, request a free GUID from the
[Australian Business Register](https://abr.business.gov.au/Tools/WebServices) and add it to your
`.env` file:

```dotenv
ABR_GUID=your-guid-here
```

## Usage

### Validation rules

Each rule checks input with the matching value object described below, so it reads input the
same way: an ABN typed with spaces passes, and one with a letter fails. Every rule comes as a
rule object and as a string rule:

| Rule object      | String rule   | Passes                                    |
|------------------|---------------|-------------------------------------------|
| `new Abn`        | `abn`         | a valid ABN                               |
| `new Acn`        | `acn`         | a valid ACN                               |
| `new AbnOrAcn`   | `abn_or_acn`  | a valid ABN (11 digits) or ACN (9 digits) |
| `new Bsb`        | `bsb`         | a BSB with 6 digits                       |
| `new AuPhone`    | `au_phone`    | a mobile or landline number               |
| `new AuPostcode` | `au_postcode` | a postcode in any state                   |

```php
use EastpointLabs\LaravelAu\Rules\AbnOrAcn;
use EastpointLabs\LaravelAu\Rules\Bsb;

$request->validate([
    'supplier_number' => ['required', new AbnOrAcn],
    'bsb'             => ['nullable', new Bsb],
]);

// The same, with string rules
$request->validate([
    'supplier_number' => 'required|abn_or_acn',
    'bsb'             => 'nullable|bsb',
]);
```

The rule objects live in `EastpointLabs\LaravelAu\Rules`. `Abn`, `Acn` and `Bsb` share their
names with the value objects in `EastpointLabs\LaravelAu\Support`, so alias one if a file needs
both.

`null` passes, so pair each rule with `required` or `nullable` as you would any other. Values
that aren't strings, such as ints and arrays, fail.

#### Phone number rules

`AuPhone` allows mobiles and landlines by default. Use a named constructor, or pass the
`PhoneType` cases you want:

```php
use EastpointLabs\LaravelAu\Rules\AuPhone;
use EastpointLabs\LaravelAu\Support\PhoneType;

new AuPhone;                                        // mobile or landline
AuPhone::mobile();                                  // mobile only
AuPhone::landline();                                // landline only
AuPhone::any();                                     // mobile, landline or service
new AuPhone(PhoneType::Mobile, PhoneType::Service); // mobile or service
```

The string rule takes the types as parameters: `au_phone:mobile` or
`au_phone:mobile,landline,service`. An unknown type throws an `InvalidArgumentException`,
because it's a mistake in your code rather than in the input. See
[Mobiles, landlines and service numbers](#mobiles-landlines-and-service-numbers) for how a
number's type is decided.

#### Postcode rules

`AuPostcode` passes a postcode from any state. Give it a state to require a postcode from that
state:

```php
use EastpointLabs\LaravelAu\Rules\AuPostcode;
use EastpointLabs\LaravelAu\Support\State;

new AuPostcode;             // any state
new AuPostcode(State::NSW); // New South Wales only
new AuPostcode('NSW');      // the same: a state code or name, in any case
```

The string forms are `au_postcode` and `au_postcode:NSW`. With NSW as the state, `3000` fails
with "The postcode must be in New South Wales."

Only pass states from your own code, never user input. An unknown state string, such as
`new AuPostcode('Atlantis')`, throws an `InvalidArgumentException`, because it's treated as a
typo in your code rather than a validation failure. To check a state the user chose, use
`matchingStateField()`.

#### Matching a postcode to the chosen state

There's no custom rule for state fields: use Laravel's `Rule::enum(State::class)`. Then
`AuPostcode::matchingStateField()` checks the postcode against the state the user picked:

```php
use EastpointLabs\LaravelAu\Rules\AuPostcode;
use EastpointLabs\LaravelAu\Support\State;
use Illuminate\Validation\Rule;

$request->validate([
    'state'    => ['required', Rule::enum(State::class)],
    'postcode' => ['required', AuPostcode::matchingStateField('state')],
]);
```

With `state` set to `NSW`, the postcode `2000` passes and `3000` fails with "The postcode must
be in New South Wales."

The state is read with `State::fromLoose()`, so a code or full name in any case works. An empty
or unrecognised state is ignored, and the postcode only has to be valid in some state, so the
rule never throws on user input. That's why the state field needs its own
`Rule::enum(State::class)`: it's what reports a bad state.

In nested arrays, a field name without a dot is read from the sibling, so each address is
checked against its own state:

```php
$request->validate([
    'addresses.*.state'    => ['required', Rule::enum(State::class)],
    'addresses.*.postcode' => ['required', AuPostcode::matchingStateField('state')],
]);
```

Here `addresses.0.postcode` is checked against `addresses.0.state`. A field name with a dot,
such as `matchingStateField('billing.state')`, is an absolute path from the top of the data.

#### Messages

The messages are in Australian English, under the `au::validation` translation namespace. For
example, "The abn must be a valid ABN." and "The postcode must be in New South Wales."

To change them, publish the translations with the `au-translations` tag (see
[Installation](#installation)) and edit `lang/vendor/au/en/validation.php`. To translate them,
add the same file for another locale, such as `lang/vendor/au/fr/validation.php`.

For the string rules, your app's own messages win, as they do for Laravel's built-in rules: an
inline custom message, or a line keyed by the rule name (such as `abn`) in your app's
`lang/en/validation.php`.

### ABN, ACN and BSB

`Abn`, `Acn` and `Bsb` are immutable value objects. They accept a number the way people type
it, keep only the digits, and format it for display.

```php
use EastpointLabs\LaravelAu\Support\Abn;

$abn = Abn::from('51 824 753 556');

$abn->value;                  // "51824753556"
$abn->format();               // "51 824 753 556"
(string) $abn;                // "51824753556"
json_encode(['abn' => $abn]); // {"abn":"51824753556"}
```

Store `value`, and use `format()` when you show the number to people. Input may contain spaces,
hyphens, dots and non-breaking spaces. Any other character, such as a letter or a tab, makes it
invalid.

Check or parse input with `isValid()`, `tryFrom()` or `from()`:

```php
Abn::isValid('51 824 753 556'); // true
Abn::isValid('51 824 753 557'); // false: bad checksum

Abn::tryFrom('51 824 753 557'); // null
Abn::from('51 824 753 557');    // throws InvalidValueException
```

`tryFrom()` returns `null` for null, blank or invalid input, so it suits optional fields.
`from()` throws `EastpointLabs\LaravelAu\Exceptions\InvalidValueException`, which extends
`InvalidArgumentException`. Its message never includes the input, which may be personal
information.

Two objects are equal when their digits match:

```php
Abn::from('51824753556')->equals(Abn::from('51-824-753-556')); // true
```

`Acn` and `Bsb` work the same way:

```php
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Bsb;

Acn::from('004085616')->format(); // "004 085 616"
Acn::isValid('000 000 018');      // false: bad check digit

Bsb::from('062000')->format();    // "062-000"
```

A BSB has no check digit, so `Bsb` only checks that there are six digits. It can't tell you
whether the branch exists.

#### Finding the ACN in an ABN

A company's ABN is usually two digits followed by its ACN. `acn()` returns that ACN when the
last nine digits form a valid one, and `null` otherwise:

```php
Abn::from('53 004 085 616')->acn()?->format(); // "004 085 616"
Abn::from('51 824 753 556')->acn();            // null
```

This is a check-digit match, not a register lookup. It doesn't prove the ABN and ACN belong to
the same company.

### States and postcodes

`State` is a string-backed enum with one case for each state and mainland territory: `NSW`,
`VIC`, `QLD`, `SA`, `WA`, `TAS`, `NT` and `ACT`. Each case's value is its code, so it stores
as a short string and works with Laravel's enum cast and `Rule::enum()`.

```php
use EastpointLabs\LaravelAu\Support\State;

State::VIC->value;   // "VIC"
State::VIC->label(); // "Victoria"

State::options();    // ['NSW' => 'New South Wales', 'VIC' => 'Victoria', ...]
```

`options()` maps codes to labels in case order, ready for a select field.

To read a state the way people type it, use `fromLoose()`. It matches a code or full name in
any case, ignores surrounding whitespace and allows one trailing dot. It returns `null` for
null, blank or unknown input.

```php
State::fromLoose('nsw');             // State::NSW
State::fromLoose('New South Wales'); // State::NSW
State::fromLoose(' Vic. ');          // State::VIC
State::fromLoose('New Zealand');     // null
```

`Postcode` is a value object that works like `Abn`, with `from()`, `tryFrom()`, `isValid()`,
`value`, `format()` and `equals()`. It also knows which state it belongs to. The value is a
string, so leading zeros are kept.

```php
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;

$postcode = Postcode::from('0800');

$postcode->value;                       // "0800"
$postcode->format();                    // "0800"
(string) $postcode;                     // "0800"
json_encode(['postcode' => $postcode]); // {"postcode":"0800"}

$postcode->state();               // State::NT
$postcode->belongsTo(State::NT);  // true
$postcode->belongsTo(State::QLD); // false
```

`belongsTo()` checks a postcode against a state. To do this in form validation, use
[`AuPostcode::matchingStateField()`](#matching-a-postcode-to-the-chosen-state).

A postcode is valid when it has exactly 4 digits and falls in a state's postcode range.
Surrounding whitespace is ignored, including non-breaking spaces. Anything else, such as a
space inside the number, makes it invalid.

```php
Postcode::isValid('2600'); // true
Postcode::isValid('800');  // false: needs all 4 digits
Postcode::isValid('0500'); // false: in no state's range

Postcode::tryFrom('800');  // null
Postcode::from('800');     // throws InvalidValueException
```

The ranges show a postcode is plausible, not that it exists, so a valid `Postcode` may not be
in use. Jervis Bay (2540) and Norfolk Island (2899) fall inside NSW ranges, so they count as
NSW.

#### Finding the state for a postcode

`State::fromPostcode()` returns the state for a postcode, or `null` when it's in no state's
range. A string follows the same rules as `Postcode`. An int can't hold a leading zero, so
it's zero-padded to 4 digits.

```php
State::fromPostcode('2000'); // State::NSW
State::fromPostcode('0800'); // State::NT
State::fromPostcode('800');  // null: a string needs all 4 digits
State::fromPostcode(800);    // State::NT: read as "0800"
State::fromPostcode(10000);  // null
```

Don't write the leading zero in an int. PHP reads an int literal that starts with `0` as
octal: `0200` is 128, which is in no state's range, and `0800` is a parse error. Pass the
string `'0800'` or the int `800` instead.

`postcodeRanges()` returns a state's inclusive ranges as ints, so `200` means 0200:

```php
State::ACT->postcodeRanges(); // [[200, 299], [2600, 2618], [2900, 2920]]
```

### Phone numbers

`PhoneNumber` is a value object for Australian mobile, landline and service (13, 1300 and 1800)
numbers. It works like `Abn`, with `from()`, `tryFrom()`, `isValid()`, `value`, `format()` and
`equals()`. The examples below use numbers that the Australian Communications and Media
Authority (ACMA) reserves for use in fiction.

```php
use EastpointLabs\LaravelAu\Support\PhoneNumber;

$phone = PhoneNumber::from('+61 491 570 156');

$phone->value;                    // "0491570156"
$phone->format();                 // "0491 570 156"
$phone->formatInternational();    // "+61 491 570 156"
$phone->toE164();                 // "+61491570156"
(string) $phone;                  // "0491570156"
json_encode(['phone' => $phone]); // {"phone":"0491570156"}
```

`value` holds the national digits, so a number is stored the same way however it was typed.
Use `format()` to show it to people in Australia and `formatInternational()` for people
overseas. `toE164()` gives the compact international form that SMS and calling services
usually expect.

Input may contain spaces, hyphens, dots, parentheses and non-breaking spaces. A leading `+61`
or `61` becomes the trunk `0`, and the `+61 (0)` form works too. Numbers from other countries
are rejected, and any other character, such as a letter or a tab, makes the input invalid.

```php
PhoneNumber::isValid('0491-570-156');       // true
PhoneNumber::isValid('+61 (0)2 5550 1234'); // true
PhoneNumber::isValid('0491 570 15');        // false: too few digits

PhoneNumber::tryFrom('0491 570 15'); // null
PhoneNumber::from('0491 570 15');    // throws InvalidValueException
```

Two numbers are equal when their national digits match:

```php
PhoneNumber::from('0491-570-156')->equals(PhoneNumber::from('+61 (0)491 570 156')); // true
```

#### Mobiles, landlines and service numbers

The leading digits decide the type. A mobile starts with `04` and a landline with `02`, `03`,
`07` or `08`, and both have 10 digits. A service number is `1300` or `1800` followed by 6
digits, or `13` followed by 4.

`type()` returns a `PhoneType`, a string-backed enum with the cases `Mobile` (`'mobile'`),
`Landline` (`'landline'`) and `Service` (`'service'`). `isMobile()`, `isLandline()` and
`isService()` are shortcuts for the same check.

```php
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\PhoneType;

$landline = PhoneNumber::from('03 5550 1234');

$landline->type() === PhoneType::Landline; // true
$landline->isLandline();                   // true
$landline->areaCode();                     // "03"
$landline->format();                       // "(03) 5550 1234"
$landline->formatInternational();          // "+61 3 5550 1234"
```

`areaCode()` returns `"02"`, `"03"`, `"07"` or `"08"` for a landline, and `null` for anything
else.

Service numbers can't be dialled from overseas, so `toE164()` and `formatInternational()`
return `null` for them:

```php
$service = PhoneNumber::from('1800 160 401');

$service->isService();           // true
$service->format();              // "1800 160 401"
$service->toE164();              // null
$service->formatInternational(); // null

PhoneNumber::from('1300-975-707')->format(); // "1300 975 707"
PhoneNumber::from('13 1234')->format();      // "13 12 34"
```

### ABN Lookup

`Abr` looks up businesses on the Australian Business Register through its free ABN Lookup web
services. It needs the `ABR_GUID` from [Installation](#installation). The validation rules and
value objects don't, so you can skip the GUID if those are all you use.

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

#### Reading the results

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

#### Errors

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

#### Caching and settings

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

#### Keeping the GUID private

The GUID never appears in exception messages, stack trace arguments or cache keys. ABN Lookup
takes it as a query parameter, though, so tools that record outgoing HTTP requests, such as
Laravel Telescope, will see it in the request URL.

#### Testing your app

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

### Filament fields

If your app uses Filament 4 or 5, these form fields bring the package's validation, formatting
and ABN Lookup into your forms. Filament is optional: the package doesn't require it, and
everything else works without it. The fields are building blocks for your own resource forms.
The package adds no pages, resources or plugin, so there's nothing to register.

```php
use EastpointLabs\LaravelAu\Filament\Forms\AbnInput;
use EastpointLabs\LaravelAu\Filament\Forms\AcnInput;
use EastpointLabs\LaravelAu\Filament\Forms\AuAddressFields;
use EastpointLabs\LaravelAu\Filament\Forms\AuPhoneInput;
use EastpointLabs\LaravelAu\Filament\Forms\BsbInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        AbnInput::make('abn')->lookup('business_name'),
        TextInput::make('business_name'),
        AcnInput::make('acn'),
        BsbInput::make('bsb'),
        AuPhoneInput::make('phone')->mobileOnly(),
        AuAddressFields::make('billing_'),
    ]);
}
```

The fields live in `EastpointLabs\LaravelAu\Filament\Forms`, and each one works with just
`make()`:

- `AbnInput` is masked as `51 824 753 556`, checked with the `Abn` rule and saved as 11 digits.
- `AcnInput` is masked as `004 085 616`, checked with the `Acn` rule and saved as 9 digits.
- `BsbInput` is masked as `062-000`, checked with the `Bsb` rule and saved as 6 digits.
- `AuPhoneInput` is a `tel` input that accepts mobiles and landlines. It saves in E.164, such as
  `+61491570006`, or as national digits (`0491570006`) when `phone.store_as` in `config/au.php`
  is `national`. Service numbers have no E.164 form, so they're always saved as national
  digits.
- `AuPostcodeInput` takes up to 4 characters, brings up the numeric keypad and is checked with
  the `AuPostcode` rule. It's saved as a string, so `0800` keeps its leading zero.
- `StateSelect` lists the eight states and territories by name, and saves the code (`NSW`,
  `VIC`...).

When a form loads a stored ABN, ACN, BSB or phone number, the field shows it formatted, such as
`51 824 753 556` or `0491 570 006`. A stored value that isn't valid is shown as it is.

#### Phone and postcode options

`AuPhoneInput` takes the same choices as the [`AuPhone` rule](#phone-number-rules):

```php
AuPhoneInput::make('phone');                                      // mobile or landline
AuPhoneInput::make('phone')->mobileOnly();                        // mobile only
AuPhoneInput::make('phone')->landlineOnly();                      // landline only
AuPhoneInput::make('phone')->allowServiceNumbers();               // mobile, landline or service
AuPhoneInput::make('phone')->mobileOnly()->allowServiceNumbers(); // mobile or service
```

`allowServiceNumbers()` adds 13, 1300 and 1800 numbers, whichever order you call it in.

`AuPostcodeInput::forState()` checks the postcode against the state chosen in another field:

```php
StateSelect::make('state'),
AuPostcodeInput::make('postcode')->forState('state'),
```

With `NSW` chosen, `3000` fails with "The postcode must be in New South Wales." While the state
field is empty, or holds something that isn't a state, a postcode from any state passes.
`StateSelect` reports a bad state itself.

#### Address fields

`AuAddressFields::make()` returns a two-column `Grid` holding the five fields of an address,
named with the prefix you give it. `AuAddressFields::make('billing_')` gives:

| Field                   | Label          | Required |
|-------------------------|----------------|----------|
| `billing_street_line_1` | Street address | Yes      |
| `billing_street_line_2` | Address line 2 | No       |
| `billing_suburb`        | Suburb         | Yes      |
| `billing_state`         | State          | Yes      |
| `billing_postcode`      | Postcode       | Yes      |

The street lines and suburb take the full width, with the state and postcode side by side. The
state is a `StateSelect`, and the postcode is checked against it with `forState()`. Without a
prefix, the fields are named `street_line_1`, `street_line_2` and so on. It's an ordinary
Filament `Grid`, so methods such as `->columnSpanFull()` chain on.

#### Filling in the business name

`lookup()` saves people typing a name the register already has. In the example above,
`AbnInput::make('abn')->lookup('business_name')` looks the ABN up when the field loses focus,
and fills `business_name` with the first business name, else the entity name. That replaces
anything already in the field. It needs the `ABR_GUID` from [Installation](#installation).

Only a valid ABN is looked up, and a lookup never breaks the form. When the ABN isn't on the
register, is suppressed and so has no name, or can't be looked up because no GUID is set or ABN
Lookup is unreachable, the other field is left as it is. A failed lookup is logged as a warning,
without the GUID. Successful lookups are cached like any other (see
[Caching and settings](#caching-and-settings)), so looking up the same ABN again sends no
request.

In tests, use `Abr::fake()` so no request is sent (see [Testing your app](#testing-your-app)).
For a resource page such as `CreateSupplier`:

```php
use EastpointLabs\LaravelAu\Facades\Abr;
use Livewire\Livewire;

it('fills the business name from the ABN', function (): void {
    Abr::fake(['51824753556' => ['EntityName' => 'Example Holdings Pty Ltd']]);

    Livewire::test(CreateSupplier::class)
        ->fillForm(['abn' => '51 824 753 556'])
        ->assertSchemaStateSet(['business_name' => 'Example Holdings Pty Ltd']);
});
```

Only ABN Lookup errors are caught, so after `preventStrayLookups()` a test that looks up an ABN
you haven't configured still fails.

#### Labels

Filament makes a label from the field name, so `abn` would be labelled "Abn". These fields set
their own, whatever the field is called: "ABN", "ACN", "BSB", "Phone", "Postcode" and "State",
plus "Street address", "Address line 2" and "Suburb" in `AuAddressFields`. `->label()` still
overrides them:

```php
AbnInput::make('supplier_abn')->label('Supplier ABN'),
```

The labels come from the package's translations, under `au::fields`, and are translated when
shown, so the current locale applies. To change or translate them, publish the translations
with the `au-translations` tag (see [Installation](#installation)) and edit
`lang/vendor/au/en/fields.php`.

### Planned for v1

The rest of the API is documented here as each feature lands:

- **Eloquent casts:** store canonical values and read them back as value objects.
- **Active ABN rule:** `abn_active`, which checks the ABN is active on the Australian Business
  Register.
- **Faker provider:** `fake()->abn()`, `fake()->auMobileNumber()` and friends for factories.
- **Filament table columns and infolist entries:** show stored ABNs, ACNs, BSBs, phone numbers
  and states formatted, to match the form fields.
- **Demo app:** `composer serve` starts a Filament panel showing every field.

## Testing

```bash
composer test     # Pest
composer check    # Pint, PHPStan and Pest, the same as CI
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Security issues: see [SECURITY.md](SECURITY.md).

## Credits

Built by [Luke Johnstone](https://eastpointlabs.com.au) at Eastpoint Labs.

## Licence

MIT. See [LICENSE.md](LICENSE.md).
