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

Publish the config file if you want to change the defaults:

```bash
php artisan vendor:publish --tag="laravel-au-config"
```

To use ABN Lookup, request a free GUID from the
[Australian Business Register](https://abr.business.gov.au/Tools/WebServices) and add it to your
`.env` file:

```dotenv
ABR_GUID=your-guid-here
```

## Usage

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

`belongsTo()` is handy for checking a postcode against the state someone picked in a form.

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

### Planned for v1

The rest of the API is documented here as each feature lands:

- **Value objects:** `PhoneNumber`, with parsing, validation and formatting.
- **Validation rules:** rule objects and string rules (`abn`, `acn`, `abn_or_acn`, `bsb`,
  `au_phone`, `au_postcode`), plus `abn_active`, which checks the ABN is active on the
  Australian Business Register.
- **Eloquent casts:** store canonical values and read them back as value objects.
- **ABN Lookup client:** look up an ABN or ACN, or search by business name, with caching, and
  `Abr::fake()` for testing your own code.
- **Faker provider:** `fake()->abn()`, `fake()->auMobileNumber()` and friends for factories.
- **Filament fields:** `AbnInput` (with optional lookup that fills the business name),
  `AcnInput`, `BsbInput`, `AuPhoneInput`, `AuPostcodeInput`, `StateSelect` and
  `AuAddressFields`, plus matching table columns and infolist entries.
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
