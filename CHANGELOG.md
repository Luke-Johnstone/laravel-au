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
