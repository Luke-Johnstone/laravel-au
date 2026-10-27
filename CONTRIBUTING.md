# Contributing

Thanks for helping improve `laravel-au`. Bug reports, fixes and new Australian-specific helpers
are all welcome.

## Getting started

```bash
git clone https://github.com/Luke-Johnstone/laravel-au.git
cd laravel-au
composer install
composer check
```

`composer check` runs everything CI runs: Pint (code style), PHPStan (static analysis) and Pest
(tests). Please make sure it passes before opening a pull request.

## Guidelines

- **Tests first.** Every change needs a test. Validation logic should be covered with datasets of
  valid, invalid and edge-case inputs.
- **No network in tests.** The test suite blocks real HTTP requests. Fake ABN Lookup responses with
  `Http::fake()` and the fixtures in `tests/Fixtures/abr`.
- **No real personal data.** Use the fictitious phone numbers reserved by ACMA (for example
  `0491 570 156` or `(02) 5550 1234`) and ABNs of public bodies. Never commit an ABR GUID.
- **Keep the core framework-light.** Classes in `src/Support` are plain PHP. Only `src/Filament`
  may use Filament.
- **Conventional Commits.** For example `feat(rules): add au_postcode string rule` or
  `fix(phone): accept numbers with a 61 prefix`.
- **Document public changes** in `README.md` and under `[Unreleased]` in `CHANGELOG.md`.

## Reporting security issues

Please see [SECURITY.md](SECURITY.md). Don't open a public issue for vulnerabilities.
