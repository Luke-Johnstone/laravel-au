# laravel-au

[![Tests](https://github.com/Luke-Johnstone/laravel-au/actions/workflows/ci.yml/badge.svg)](https://github.com/Luke-Johnstone/laravel-au/actions/workflows/ci.yml)
[![Latest version](https://img.shields.io/packagist/v/eastpointlabs/laravel-au.svg)](https://packagist.org/packages/eastpointlabs/laravel-au)
[![Licence](https://img.shields.io/packagist/l/eastpointlabs/laravel-au.svg)](LICENSE.md)
[![PHP version](https://img.shields.io/packagist/dependency-v/eastpointlabs/laravel-au/php.svg)](https://packagist.org/packages/eastpointlabs/laravel-au)
[![Laravel version](https://img.shields.io/packagist/dependency-v/eastpointlabs/laravel-au/illuminate/support.svg?label=laravel)](https://packagist.org/packages/eastpointlabs/laravel-au)
[![Total downloads](https://img.shields.io/packagist/dt/eastpointlabs/laravel-au.svg)](https://packagist.org/packages/eastpointlabs/laravel-au)

Validate Australian business details in Laravel, check ABNs against the Australian Business
Register, and add ready-made fields to Filament forms.

![The ABN field rejecting an invalid ABN and a cancelled one, filling in the business name for an active ABN, then checking the postcode against the state](https://raw.githubusercontent.com/Luke-Johnstone/laravel-au/main/docs/images/filament-form.gif)

## What it does

**Catches bad details before they're saved.** Rules for ABNs, ACNs, BSBs, phone numbers and
postcodes, including whether a postcode belongs to the selected state.

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

**Checks a business on the register.** A valid checksum doesn't mean a business exists. With a
free GUID, the [active ABN rule][active-abn-rule] rejects an ABN that's cancelled or not on the
register, and can require GST registration. [`Abr`][abn-lookup] gives you the business's
details in your own code.

```php
use EastpointLabs\LaravelAu\Facades\Abr;
use EastpointLabs\LaravelAu\Rules\ActiveAbn;

$request->validate([
    'abn' => ['required', (new ActiveAbn)->requireGst()],
]);

Abr::abn('51 824 753 556')?->displayName(); // the business name, or null if not found or hidden
```

**Adds Australian fields to Filament.** [Form fields][filament-fields] format and validate as
people type. `AbnInput` can fill in the business name from the ABN, and `AuAddressFields` adds a
full address with the postcode checked against the state.

```php
use EastpointLabs\LaravelAu\Filament\Forms\AbnInput;
use EastpointLabs\LaravelAu\Filament\Forms\AuAddressFields;
use Filament\Forms\Components\TextInput;

AbnInput::make('abn')->lookup('business_name'),
TextInput::make('business_name'),
AuAddressFields::make(),
```

It also includes [value objects][value-objects], [Eloquent casts][eloquent-casts] and a
[Faker provider][faker-provider] for test data. Use what you need and ignore the rest.

## Why

Almost every Australian Laravel project ends up rewriting the same helpers: an ABN checksum, a
phone formatter, a postcode check. This package collects them in one well-tested place, with
optional Filament form fields on top.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 4 or 5 (optional, only for the form fields, table columns and infolist entries)

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

## Configuration

Everything has a default, so you only need to publish `config/au.php` to change one. It has two
sections:

- `abr`: the ABN Lookup GUID, timeout and caching, all set from `.env`. See
  [Caching and settings][caching-and-settings].
- `phone.store_as`: whether phone numbers are saved in E.164 (`e164`, the default) or as
  national digits (`national`). The [Eloquent cast][storing-phone-numbers] and the
  [Filament phone field][filament-fields] both use it.

## Documentation

- [Validation rules][validation-rules]: rule objects and string rules for ABNs, ACNs, BSBs,
  phone numbers and postcodes, including the postcode and state match and the active ABN check.
- [Value objects][value-objects]: `Abn`, `Acn`, `Bsb`, `PhoneNumber`, `Postcode` and the `State`
  enum, for parsing, formatting and comparing.
- [Eloquent casts and Faker provider][casts-and-faker]: store value objects on your models and
  generate valid test data.
- [ABN Lookup][abn-lookup]: look up a business on the register, cache the results, and fake
  lookups in your tests.
- [Filament fields][filament-fields]: form fields, address fields, table columns and infolist
  entries for Filament 4 and 5.

## Try the demo

The repository includes a small Filament app that uses every field, column and entry. Its code
is in `workbench/`, and it isn't included when you install the package. From a clone:

```bash
composer install
composer serve
```

Open `http://127.0.0.1:8000/admin`. The demo user is signed in automatically, in the local
environment only. Without `ABR_GUID`, ABN lookup answers from [`Abr::fake()`][testing-your-app],
so the demo works offline: type `51 824 753 556` in the ABN field and press Tab, and the business
name fills in. Each `composer serve` rebuilds the database, so anything you save is lost.

## Roadmap

Ideas for later versions:

- ARBN and ARSN, the numbers for registered bodies and managed investment schemes. They use the
  same check digit as the ACN.
- `State::timezone()`.
- An `au:abn` Artisan command to look up an ABN from the terminal.
- Livewire property synthesisers, so the value objects can be Livewire component properties.
- Livewire components for apps that don't use Filament.

## Deliberately excluded

- **Tax file numbers.** The Privacy (Tax File Number) Rule 2015 limits who can collect TFNs,
  why, and how they're stored. A TFN rule would make collecting them look routine, so the
  package doesn't have one.
- **BSB directory lookup.** AusPayNet licenses the BSB directory, so `Bsb` checks the format
  only and can't tell you whether a branch exists.
- **Address autocomplete and postcode-to-suburb data.** The data is licensed, and finding an
  address is a different job from checking one. The postcode checks use public state ranges
  only.

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

[validation-rules]: https://github.com/Luke-Johnstone/laravel-au/blob/main/docs/validation.md
[active-abn-rule]: https://github.com/Luke-Johnstone/laravel-au/blob/main/docs/validation.md#active-abn-rule
[value-objects]: https://github.com/Luke-Johnstone/laravel-au/blob/main/docs/value-objects.md
[casts-and-faker]: https://github.com/Luke-Johnstone/laravel-au/blob/main/docs/casts-and-faker.md
[eloquent-casts]: https://github.com/Luke-Johnstone/laravel-au/blob/main/docs/casts-and-faker.md#eloquent-casts
[storing-phone-numbers]: https://github.com/Luke-Johnstone/laravel-au/blob/main/docs/casts-and-faker.md#storing-phone-numbers
[faker-provider]: https://github.com/Luke-Johnstone/laravel-au/blob/main/docs/casts-and-faker.md#faker-provider
[abn-lookup]: https://github.com/Luke-Johnstone/laravel-au/blob/main/docs/abn-lookup.md
[caching-and-settings]: https://github.com/Luke-Johnstone/laravel-au/blob/main/docs/abn-lookup.md#caching-and-settings
[testing-your-app]: https://github.com/Luke-Johnstone/laravel-au/blob/main/docs/abn-lookup.md#testing-your-app
[filament-fields]: https://github.com/Luke-Johnstone/laravel-au/blob/main/docs/filament.md
