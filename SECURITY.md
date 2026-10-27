# Security policy

## Supported versions

Only the latest major version receives security fixes.

## Reporting a vulnerability

Please email **luke@eastpointlabs.com.au** with the details, or use GitHub's
[private vulnerability reporting](https://github.com/Luke-Johnstone/laravel-au/security/advisories/new).
Don't open a public issue.

You can expect an acknowledgement within a few business days. Once a fix is released you'll be
credited in the release notes, unless you'd prefer not to be.

## Handling of secrets and personal information

- The ABR authentication GUID is read from configuration only. The package never logs it, and it
  is stripped from exception messages.
- Exception messages never include the value that failed validation, because phone numbers and
  similar values can be personal information.
