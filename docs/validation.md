# Validation rules

This page is part of the [laravel-au](../README.md) docs. For setup, see
[Installation](../README.md#installation).

Each rule checks input with the matching [value object](value-objects.md), so it reads input
the same way: an ABN typed with spaces passes, and one with a letter fails. Every rule comes as
a rule object and as a string rule:

| Rule object      | String rule   | Passes                                    |
|------------------|---------------|-------------------------------------------|
| `new Abn`        | `abn`         | a valid ABN                               |
| `new Acn`        | `acn`         | a valid ACN                               |
| `new AbnOrAcn`   | `abn_or_acn`  | a valid ABN (11 digits) or ACN (9 digits) |
| `new Bsb`        | `bsb`         | a BSB with 6 digits                       |
| `new AuPhone`    | `au_phone`    | a mobile or landline number               |
| `new AuPostcode` | `au_postcode` | a postcode in any state                   |
| `new ActiveAbn`  | `abn_active`  | an active ABN, checked with ABN Lookup    |

```php
use EastpointLabs\LaravelAu\Rules\AbnOrAcn;
use EastpointLabs\LaravelAu\Rules\Bsb;

$request->validate([
    'supplier_number' => ['required', new AbnOrAcn],
    'bsb'             => ['nullable', new Bsb],
]);

// The same, with string rules
$request->validate([
    'supplier_number' => 'required|abn_or_acn',
    'bsb'             => 'nullable|bsb',
]);
```

The rule objects live in `EastpointLabs\LaravelAu\Rules`. `Abn`, `Acn` and `Bsb` share their
names with the value objects in `EastpointLabs\LaravelAu\Support`, so alias one if a file needs
both.

`null` passes, so pair each rule with `required` or `nullable` as you would any other. Values
that aren't strings, such as ints and arrays, fail.

## Phone number rules

`AuPhone` allows mobiles and landlines by default. Use a named constructor, or pass the
`PhoneType` cases you want:

```php
use EastpointLabs\LaravelAu\Rules\AuPhone;
use EastpointLabs\LaravelAu\Support\PhoneType;

new AuPhone;                                        // mobile or landline
AuPhone::mobile();                                  // mobile only
AuPhone::landline();                                // landline only
AuPhone::any();                                     // mobile, landline or service
new AuPhone(PhoneType::Mobile, PhoneType::Service); // mobile or service
```

The string rule takes the types as parameters: `au_phone:mobile` or
`au_phone:mobile,landline,service`. An unknown type throws an `InvalidArgumentException`,
because it's a mistake in your code rather than in the input. See
[Mobiles, landlines and service numbers](value-objects.md#mobiles-landlines-and-service-numbers)
for how a number's type is decided.

## Postcode rules

`AuPostcode` passes a postcode from any state. Give it a state to require a postcode from that
state:

```php
use EastpointLabs\LaravelAu\Rules\AuPostcode;
use EastpointLabs\LaravelAu\Support\State;

new AuPostcode;             // any state
new AuPostcode(State::NSW); // New South Wales only
new AuPostcode('NSW');      // the same: a state code or name, in any case
```

The string forms are `au_postcode` and `au_postcode:NSW`. With NSW as the state, `3000` fails
with "The postcode must be in New South Wales."

Only pass states from your own code, never user input. An unknown state string, such as
`new AuPostcode('Atlantis')`, throws an `InvalidArgumentException`, because it's treated as a
typo in your code rather than a validation failure. To check a state the user chose, use
`matchingStateField()`.

## Matching a postcode to the chosen state

There's no custom rule for state fields: use Laravel's `Rule::enum(State::class)`. Then
`AuPostcode::matchingStateField()` checks the postcode against the state the user picked:

```php
use EastpointLabs\LaravelAu\Rules\AuPostcode;
use EastpointLabs\LaravelAu\Support\State;
use Illuminate\Validation\Rule;

$request->validate([
    'state'    => ['required', Rule::enum(State::class)],
    'postcode' => ['required', AuPostcode::matchingStateField('state')],
]);
```

With `state` set to `NSW`, the postcode `2000` passes and `3000` fails with "The postcode must
be in New South Wales."

The state is read with `State::fromLoose()`, so a code or full name in any case works. An empty
or unrecognised state is ignored, and the postcode only has to be valid in some state, so the
rule never throws on user input. That's why the state field needs its own
`Rule::enum(State::class)`: it's what reports a bad state.

In nested arrays, a field name without a dot is read from the sibling, so each address is
checked against its own state:

```php
$request->validate([
    'addresses.*.state'    => ['required', Rule::enum(State::class)],
    'addresses.*.postcode' => ['required', AuPostcode::matchingStateField('state')],
]);
```

Here `addresses.0.postcode` is checked against `addresses.0.state`. A field name with a dot,
such as `matchingStateField('billing.state')`, is an absolute path from the top of the data.

## Active ABN rule

A checksum shows an ABN is well formed, not that the business exists. `ActiveAbn` checks that
the ABN is on the Australian Business Register and that its status is active. `requireGst()`,
or `abn_active:gst`, also requires it to be registered for GST:

```php
use EastpointLabs\LaravelAu\Rules\ActiveAbn;

$request->validate([
    'abn' => ['required', (new ActiveAbn)->requireGst()],
]);

// The same, with a string rule
$request->validate([
    'abn' => 'required|abn_active:gst',
]);
```

With `abn` as the field, the rule fails with:

- "The abn must be a valid ABN." when the ABN is badly formatted. It isn't looked up.
- "The abn must be an active ABN." when the ABN isn't on the register, or isn't active.
- "The abn must be registered for GST." when it's active but not registered for GST, with
  `requireGst()` or `abn_active:gst`.

A suppressed ABN is judged on its status like any other, so a suppressed ABN that's active
passes. `null` is left to `required` and `nullable`, as with the other rules.

The rule uses [ABN Lookup](abn-lookup.md), so it needs the `ABR_GUID` from
[Installation](../README.md#installation).

When the ABN can't be checked, because no GUID is set, ABN Lookup can't be reached or times out,
or it answers with an error, the rule fails with "The abn could not be checked right now. Please
try again." It also logs a warning that names the exception class only, without the GUID or the
ABN. Failing is the safe default, because an unchecked ABN never gets through. Where letting
someone through matters more than the check, call `passWhenUnavailable()` to pass instead:

```php
$request->validate([
    'abn' => ['required', (new ActiveAbn)->passWhenUnavailable()],
]);
```

The warning is still logged, and an ABN that's looked up and isn't active still fails. There's
no string rule form of `passWhenUnavailable()`, so use the rule object.

On a cache miss, the lookup is a network call made during validation, so it can add up to
`ABR_TIMEOUT` seconds (10 by default) to the response. Successful lookups are cached, for a day
by default, so checking the same ABN again is fast. Not-found results and errors aren't cached.
See [Caching and settings](abn-lookup.md#caching-and-settings). For forms, consider a lower
timeout, such as `ABR_TIMEOUT=3`, so a slow ABN Lookup doesn't keep people waiting. The timeout
applies to every lookup, not just this rule.

In tests, use `Abr::fake()` so no request is sent (see
[Testing your app](abn-lookup.md#testing-your-app)). An ABN you configure is active and not
registered for GST unless you say otherwise:

```php
use EastpointLabs\LaravelAu\Facades\Abr;
use EastpointLabs\LaravelAu\Rules\ActiveAbn;
use Illuminate\Support\Facades\Validator;

it('only accepts active ABNs', function (): void {
    Abr::fake([
        '51824753556' => [],
        '83914571673' => ['AbnStatus' => 'Cancelled'],
    ]);

    $rules = ['abn' => ['required', new ActiveAbn]];

    expect(Validator::make(['abn' => '51 824 753 556'], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['abn' => '83 914 571 673'], $rules)->passes())->toBeFalse();
});
```

Set `'Gst' => '2000-07-01'` on an ABN to make it registered for GST, and use
`Abr::fake()->failing()` to test what happens when the ABN can't be checked.

## Messages

The messages are in Australian English, under the `au::validation` translation namespace. For
example, "The abn must be a valid ABN." and "The postcode must be in New South Wales."

To change them, publish the translations with the `au-translations` tag (see
[Installation](../README.md#installation)) and edit `lang/vendor/au/en/validation.php`. To
translate them, add the same file for another locale, such as `lang/vendor/au/fr/validation.php`.

For the string rules, your app's own messages win, as they do for Laravel's built-in rules: an
inline custom message, or a line keyed by the rule name (such as `abn`) in your app's
`lang/en/validation.php`. Your message replaces every message that rule can fail with, so a
line for `abn_active` also covers an ABN that couldn't be checked.
