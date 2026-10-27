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
