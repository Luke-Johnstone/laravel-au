# Changelog

All notable changes to `laravel-au` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Value objects `Abn`, `Acn`, `Bsb`, `Postcode` and `PhoneNumber`, which accept numbers as
  people type them, validate them and format them for display. `Abn::acn()` finds the ACN in a
  company's ABN, and `PhoneNumber` gives E.164 and international forms.
- `State` enum for the eight states and territories, with labels, loose parsing, postcode ranges
  and `options()` for select fields, and `PhoneType` enum for mobile, landline and service
  numbers.
- `InvalidValueException`, thrown by `from()` for invalid input. Its message never includes the
  input.
- Validation rules `Abn`, `Acn`, `AbnOrAcn`, `Bsb`, `AuPhone` and `AuPostcode`, with matching
  string rules. `AuPostcode::matchingStateField()` checks a postcode against the state in
  another field, including in nested arrays.
- `ActiveAbn` rule and `abn_active` string rule, which check with ABN Lookup that an ABN is
  active and, with `requireGst()`, registered for GST. When the ABN can't be checked it fails
  and logs a warning, unless `passWhenUnavailable()` is set.
- Australian English validation messages and field labels, publishable with the
  `au-translations` tag.
- ABN Lookup client with `abn()`, `acn()` and `searchByName()`, through the `Abr` facade or the
  `LooksUpAbns` contract. It returns `AbnDetails` and `NameMatch` objects, caches successful
  lookups, and keeps the GUID out of exception messages, stack traces and cache keys. Errors
  throw `AbrException`, or `MissingGuidException` when no GUID is set.
- `Abr::fake()` for testing code that looks up ABNs without sending requests, with not-found,
  suppressed and failing answers, stray lookup prevention and lookup assertions.
- Eloquent casts `AsAbn`, `AsAcn`, `AsBsb`, `AsPhoneNumber` and `AsPostcode`. `AsPhoneNumber`
  stores E.164 or national digits, set by `phone.store_as` in `config/au.php`.
- Faker provider with `abn()`, `invalidAbn()`, `acn()`, `bsb()`, `auMobileNumber()`,
  `auLandlineNumber()`, `auPostcode()` and `auState()`, registered when Faker is installed.
  Phone numbers come from ACMA's ranges for fiction.
- Optional Filament 4 and 5 form fields `AbnInput`, `AcnInput`, `BsbInput`, `AuPhoneInput`,
  `AuPostcodeInput`, `StateSelect` and `AuAddressFields`. `AbnInput::lookup()` fills in the
  business name from ABN Lookup.
- Optional Filament table columns `AbnColumn`, `AcnColumn`, `BsbColumn`, `AuPhoneColumn` and
  `StateColumn`, and matching infolist entries, with `linkToAbnLookup()`, `dialable()` and
  `abbreviated()`.
- Demo Filament app, started with `composer serve`, that uses every field, column and entry and
  works offline without `ABR_GUID`. It isn't part of the Composer package.
