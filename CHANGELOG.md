# Changelog

All notable changes to `laravel-au` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Package skeleton: service provider, config, test suite, CI and static analysis.
- `Abn`, `Acn` and `Bsb` value objects that parse, validate and format Australian Business
  Numbers, Australian Company Numbers and BSBs.
- `Abn::acn()`, which returns the ACN held in the last nine digits of a company's ABN when they
  form a valid ACN.
- `InvalidValueException`, thrown by `from()` for invalid input. Its message never includes the
  input.
- `State` enum for the eight states and territories, with full-name labels, loose parsing of
  codes and names, postcode-to-state lookup and `options()` for select fields.
- `Postcode` value object that checks a postcode has 4 digits and falls in a state's range, and
  reports which state it belongs to.
- `PhoneNumber` value object that parses Australian mobile, landline and 13/1300/1800 numbers
  typed in national or +61 form, and formats them for display or as E.164.
- `PhoneType` enum (`Mobile`, `Landline`, `Service`), returned by `PhoneNumber::type()`.
- Validation rules `Abn`, `Acn`, `AbnOrAcn`, `Bsb`, `AuPhone` and `AuPostcode`, backed by the
  value objects. `AuPhone` takes the phone types to allow, and `AuPostcode` an optional state.
- String rules `abn`, `acn`, `abn_or_acn`, `bsb`, `au_phone` (such as `au_phone:mobile`) and
  `au_postcode` (such as `au_postcode:NSW`), which pass and fail like the rule objects.
- `AuPostcode::matchingStateField()`, which checks a postcode against the state in another
  field, including each item in a nested array.
- Australian English validation messages under the `au::validation` translation namespace,
  publishable with the `au-translations` tag.
- `AbnDetails` and `NameMatch` data objects for ABN Lookup records and name search results.
  `AbnDetails` has `isActive()`, `isGstRegistered()`, `isSuppressed()`, `displayName()` and
  `toArray()`. A suppressed ABN has no names or address, so its `displayName()` is `null`.
- `AbrException` for ABN Lookup errors, and its subclass `MissingGuidException`, whose
  message explains how to set `ABR_GUID`.
- `AbrClient`, an ABN Lookup client with `abn()`, `acn()` and `searchByName()`. It checks input
  before sending a request, uses the `ABR_TIMEOUT` timeout, doesn't retry, and keeps the GUID
  out of exception messages, stack traces and cache keys.
- `LooksUpAbns` contract for ABN Lookup, the type to inject in application code.
- `Abr` facade, which resolves `LooksUpAbns`.
- Caching of successful ABN and ACN lookups, for a day by default, configured with
  `ABR_CACHE_ENABLED`, `ABR_CACHE_STORE` and `ABR_CACHE_TTL`. Not-found results, errors and
  name searches aren't cached.
- `Abr::fake()`, which binds an `AbrFake` to `LooksUpAbns` so tests can look up ABNs, ACNs and
  names without sending a request. It can return not-found, suppressed and failing lookups, set
  name search results and prevent stray lookups, and has `assertLookedUp()`,
  `assertNotLookedUp()`, `assertNothingLookedUp()` and `assertLookupCount()`.
- `AbrClient::MAX_RESULTS`, the most name matches ABN Lookup returns for one search (200).
- Optional Filament 4 and 5 form fields `AbnInput`, `AcnInput`, `BsbInput`, `AuPhoneInput`,
  `AuPostcodeInput`, `StateSelect` and `AuAddressFields`. They mask and validate input, save
  canonical values and show stored values formatted.
- `AbnInput::lookup()`, which fills another field with the business name from ABN Lookup when
  the ABN field loses focus. A failed lookup is logged without the GUID and leaves the form as
  it is.
- `AuPhoneInput::mobileOnly()`, `landlineOnly()` and `allowServiceNumbers()`. The field saves
  numbers in E.164 or as national digits, set by `phone.store_as` in `config/au.php`.
- `AuPostcodeInput::forState()`, which checks the postcode against the state chosen in another
  field.
- Default field labels such as "ABN" rather than "Abn", under the `au::fields` translation
  namespace and publishable with the `au-translations` tag.
- Eloquent casts `AsAbn`, `AsAcn`, `AsBsb`, `AsPhoneNumber` and `AsPostcode`, which store the
  canonical string and read it back as the value object. Invalid values throw
  `InvalidValueException` when set or read.
- `AsPhoneNumber` stores E.164 or national digits, set by `phone.store_as` in `config/au.php`,
  the same setting the Filament phone field uses.
- Faker provider, registered automatically when Faker is installed, with `abn()`, `invalidAbn()`,
  `acn()`, `bsb()`, `auMobileNumber()`, `auLandlineNumber()`, `auPostcode()` and `auState()`.
  Phone numbers come from ACMA's fictional ranges, and postcodes match the given state.
