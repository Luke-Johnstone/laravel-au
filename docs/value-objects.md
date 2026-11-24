# Value objects

This page is part of the [laravel-au](../README.md) docs. For setup, see
[Installation](../README.md#installation).

## ABN, ACN and BSB

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

### Finding the ACN in an ABN

A company's ABN is usually two digits followed by its ACN. `acn()` returns that ACN when the
last nine digits form a valid one, and `null` otherwise:

```php
Abn::from('53 004 085 616')->acn()?->format(); // "004 085 616"
Abn::from('51 824 753 556')->acn();            // null
```

This is a check-digit match, not a register lookup. It doesn't prove the ABN and ACN belong to
the same company.

## States and postcodes

`State` is a string-backed enum with one case for each state and mainland territory: `NSW`,
`VIC`, `QLD`, `SA`, `WA`, `TAS`, `NT` and `ACT`. Each case's value is its code, so it stores
as a short string and works with Laravel's enum cast and `Rule::enum()`.

```php
use EastpointLabs\LaravelAu\Support\State;

State::VIC->value;   // "VIC"
State::VIC->label(); // "Victoria"

State::options();    // ['NSW' => 'New South Wales', 'VIC' => 'Victoria', ...]
```

`options()` maps codes to labels in case order, ready for a select field.

To read a state the way people type it, use `fromLoose()`. It matches a code or full name in
any case, ignores surrounding whitespace and allows one trailing dot. It returns `null` for
null, blank or unknown input.

```php
State::fromLoose('nsw');             // State::NSW
State::fromLoose('New South Wales'); // State::NSW
State::fromLoose(' Vic. ');          // State::VIC
State::fromLoose('New Zealand');     // null
```

`Postcode` is a value object that works like `Abn`, with `from()`, `tryFrom()`, `isValid()`,
`value`, `format()` and `equals()`. It also knows which state it belongs to. The value is a
string, so leading zeros are kept.

```php
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;

$postcode = Postcode::from('0800');

$postcode->value;                       // "0800"
$postcode->format();                    // "0800"
(string) $postcode;                     // "0800"
json_encode(['postcode' => $postcode]); // {"postcode":"0800"}

$postcode->state();               // State::NT
$postcode->belongsTo(State::NT);  // true
$postcode->belongsTo(State::QLD); // false
```

`belongsTo()` checks a postcode against a state. To do this in form validation, use
[`AuPostcode::matchingStateField()`](validation.md#matching-a-postcode-to-the-chosen-state).

A postcode is valid when it has exactly 4 digits and falls in a state's postcode range.
Surrounding whitespace is ignored, including non-breaking spaces. Anything else, such as a
space inside the number, makes it invalid.

```php
Postcode::isValid('2600'); // true
Postcode::isValid('800');  // false: needs all 4 digits
Postcode::isValid('0500'); // false: in no state's range

Postcode::tryFrom('800');  // null
Postcode::from('800');     // throws InvalidValueException
```

The ranges show a postcode is plausible, not that it exists, so a valid `Postcode` may not be
in use. Jervis Bay (2540) and Norfolk Island (2899) fall inside NSW ranges, so they count as
NSW.

### Finding the state for a postcode

`State::fromPostcode()` returns the state for a postcode, or `null` when it's in no state's
range. A string follows the same rules as `Postcode`. An int can't hold a leading zero, so
it's zero-padded to 4 digits.

```php
State::fromPostcode('2000'); // State::NSW
State::fromPostcode('0800'); // State::NT
State::fromPostcode('800');  // null: a string needs all 4 digits
State::fromPostcode(800);    // State::NT: read as "0800"
State::fromPostcode(10000);  // null
```

Don't write the leading zero in an int. PHP reads an int literal that starts with `0` as
octal: `0200` is 128, which is in no state's range, and `0800` is a parse error. Pass the
string `'0800'` or the int `800` instead.

`postcodeRanges()` returns a state's inclusive ranges as ints, so `200` means 0200:

```php
State::ACT->postcodeRanges(); // [[200, 299], [2600, 2618], [2900, 2920]]
```

## Phone numbers

`PhoneNumber` is a value object for Australian mobile, landline and service (13, 1300 and 1800)
numbers. It works like `Abn`, with `from()`, `tryFrom()`, `isValid()`, `value`, `format()` and
`equals()`. The examples below use numbers that the Australian Communications and Media
Authority (ACMA) reserves for use in fiction.

```php
use EastpointLabs\LaravelAu\Support\PhoneNumber;

$phone = PhoneNumber::from('+61 491 570 156');

$phone->value;                    // "0491570156"
$phone->format();                 // "0491 570 156"
$phone->formatInternational();    // "+61 491 570 156"
$phone->toE164();                 // "+61491570156"
(string) $phone;                  // "0491570156"
json_encode(['phone' => $phone]); // {"phone":"0491570156"}
```

`value` holds the national digits, so a number is stored the same way however it was typed.
Use `format()` to show it to people in Australia and `formatInternational()` for people
overseas. `toE164()` gives the compact international form that SMS and calling services
usually expect.

Input may contain spaces, hyphens, dots, parentheses and non-breaking spaces. A leading `+61`
or `61` becomes the trunk `0`, and the `+61 (0)` form works too. Numbers from other countries
are rejected, and any other character, such as a letter or a tab, makes the input invalid.

```php
PhoneNumber::isValid('0491-570-156');       // true
PhoneNumber::isValid('+61 (0)2 5550 1234'); // true
PhoneNumber::isValid('0491 570 15');        // false: too few digits

PhoneNumber::tryFrom('0491 570 15'); // null
PhoneNumber::from('0491 570 15');    // throws InvalidValueException
```

Two numbers are equal when their national digits match:

```php
PhoneNumber::from('0491-570-156')->equals(PhoneNumber::from('+61 (0)491 570 156')); // true
```

### Mobiles, landlines and service numbers

The leading digits decide the type. A mobile starts with `04` and a landline with `02`, `03`,
`07` or `08`, and both have 10 digits. A service number is `1300` or `1800` followed by 6
digits, or `13` followed by 4.

`type()` returns a `PhoneType`, a string-backed enum with the cases `Mobile` (`'mobile'`),
`Landline` (`'landline'`) and `Service` (`'service'`). `isMobile()`, `isLandline()` and
`isService()` are shortcuts for the same check.

```php
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\PhoneType;

$landline = PhoneNumber::from('03 5550 1234');

$landline->type() === PhoneType::Landline; // true
$landline->isLandline();                   // true
$landline->areaCode();                     // "03"
$landline->format();                       // "(03) 5550 1234"
$landline->formatInternational();          // "+61 3 5550 1234"
```

`areaCode()` returns `"02"`, `"03"`, `"07"` or `"08"` for a landline, and `null` for anything
else.

Service numbers can't be dialled from overseas, so `toE164()` and `formatInternational()`
return `null` for them:

```php
$service = PhoneNumber::from('1800 160 401');

$service->isService();           // true
$service->format();              // "1800 160 401"
$service->toE164();              // null
$service->formatInternational(); // null

PhoneNumber::from('1300-975-707')->format(); // "1300 975 707"
PhoneNumber::from('13 1234')->format();      // "13 12 34"
```
