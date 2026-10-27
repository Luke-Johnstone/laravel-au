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

### Planned for v1

The rest of the API is documented here as each feature lands:

- **Value objects:** `PhoneNumber`, `Postcode` and a `State` enum, with parsing, validation and
  formatting.
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
