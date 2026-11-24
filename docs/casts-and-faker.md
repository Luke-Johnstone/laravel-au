# Eloquent casts and Faker provider

This page is part of the [laravel-au](../README.md) docs. For setup, see
[Installation](../README.md#installation).

## Eloquent casts

The casts store a value object's canonical string in the database and give you the value object
back when you read the attribute. `State` doesn't need a cast from the package: it's a
string-backed enum, so Laravel's enum cast handles it.

```php
use EastpointLabs\LaravelAu\Casts\AsAbn;
use EastpointLabs\LaravelAu\Casts\AsAcn;
use EastpointLabs\LaravelAu\Casts\AsBsb;
use EastpointLabs\LaravelAu\Casts\AsPhoneNumber;
use EastpointLabs\LaravelAu\Casts\AsPostcode;
use EastpointLabs\LaravelAu\Support\State;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected function casts(): array
    {
        return [
            'abn'      => AsAbn::class,
            'acn'      => AsAcn::class,
            'bsb'      => AsBsb::class,
            'phone'    => AsPhoneNumber::class,
            'postcode' => AsPostcode::class,
            'state'    => State::class, // Laravel's enum cast
        ];
    }
}
```

Set an attribute to a value object, or to a string in any form its `from()` accepts. The cast
stores the canonical value, and `null` stores `null`. Reading gives back the value object, and
a stored `null` reads as `null`:

```php
use EastpointLabs\LaravelAu\Support\Postcode;

$supplier = new Supplier;

$supplier->abn = '51 824 753 556';            // stored as "51824753556"
$supplier->postcode = Postcode::from('0800'); // stored as "0800"
$supplier->acn = null;                        // stored as null

$supplier->abn->format(); // "51 824 753 556"
$supplier->acn;           // null

$supplier->toArray(); // ['abn' => '51824753556', 'postcode' => '0800', 'acn' => null]
```

`toArray()` and `toJson()` hold the stored strings, not objects, so an API response shows the
same values as the database.

A row stored in another form, such as an ABN saved with spaces before you added the cast, reads
normally and isn't marked as changed just because you read it. It's stored in canonical form the
next time you set that attribute.

Setting an invalid value throws `InvalidValueException`, as `from()` does. Check input with the
[validation rules](validation.md) first, so people see a message rather than an error.
Reading a stored value that isn't valid throws too, as Laravel's enum cast does for a value
that isn't a case. Clean up existing data before you add a cast to a column that already has
rows.

Use string columns. ACNs, BSBs, postcodes and national phone numbers can start with `0`, which
an integer column drops. A stored integer is read as its digits, so an ABN in an integer column
still works, but an ACN that has lost its leading zeros isn't valid and throws.

### Storing phone numbers

`AsPhoneNumber` stores numbers in E.164, such as `+61491570006`, by default. Set
`phone.store_as` in `config/au.php` to `national` to store national digits (`0491570006`)
instead. The [Filament phone field](filament.md) uses the same setting, so the two agree.
Service numbers have no E.164 form, so they're always stored as national digits.

```php
$supplier->phone = '0491 570 006'; // stored as "+61491570006"

$supplier->phone->value;    // "0491570006"
$supplier->phone->format(); // "0491 570 006"

$supplier->phone = '1300 975 707'; // stored as "1300975707"
```

The cast reads either form back as the same `PhoneNumber`, so rows stored before you change the
setting still load. `toArray()` and `toJson()` follow the setting too, so a phone number appears
as `+61491570006` by default. That differs from `json_encode()` on a `PhoneNumber` itself, which
gives the national digits.

## Faker provider

When Faker is installed, the package adds an Australian provider to Laravel's Faker generator,
so `fake()->abn()` and friends work in model factories and tests. Most Laravel apps already
have `fakerphp/faker` as a dev dependency. This package doesn't need it: without Faker, the
provider just isn't registered.

```php
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'abn'      => fake()->abn(),
            'acn'      => fake()->acn(),
            'bsb'      => fake()->bsb(),
            'phone'    => fake()->auMobileNumber(),
            'state'    => $state = fake()->auState(),
            'postcode' => fake()->auPostcode($state), // in the state picked above
        ];
    }
}
```

The provider adds these methods:

- `abn()`: 11 digits with a valid checksum.
- `invalidAbn()`: 11 digits that fail the ABN checksum, for testing your validation.
- `acn()`: 9 digits with a valid check digit.
- `bsb()`: any 6 digits, which is all `Bsb` checks.
- `auMobileNumber()`: one of six mobile numbers ACMA reserves for fiction, such as
  `0491570156`.
- `auLandlineNumber(?State $state = null)`: a number from ACMA's fictional `5550` block, in the
  state's area code. `auLandlineNumber(State::VIC)` gives `035550` and four more digits.
- `auPostcode(?State $state = null)`: a postcode inside one of the state's ranges, so
  `State::fromPostcode()` returns that state.
- `auState()`: a `State` case.

Without a state, `auLandlineNumber()` and `auPostcode()` pick one at random.

Values are canonical digit strings, like a value object's `value`, and `auState()` returns a
`State`. To show one formatted, pass it to the value object:

```php
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\State;

PhoneNumber::from(fake()->auLandlineNumber(State::VIC))->format(); // such as "(03) 5550 1234"
```

Seed Faker, such as with `fake()->seed(1234)`, to get the same values on every run.

> **Warning:** generated ABNs and ACNs pass the checks, but they may belong to real businesses.
> Never send them to the live ABN Lookup service. In tests, use `Abr::fake()` instead (see
> [Testing your app](abn-lookup.md#testing-your-app)). The phone numbers all come from the
> ranges ACMA reserves for fiction, so they're safe to use.
