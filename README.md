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

Full documentation is added as each feature lands. Planned for v1:

- **Value objects:** `Abn`, `Acn`, `Bsb`, `PhoneNumber`, `Postcode` and a `State` enum, with
  parsing, validation and formatting.
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
