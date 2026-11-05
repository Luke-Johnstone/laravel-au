<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Casts\AsAbn;
use EastpointLabs\LaravelAu\Casts\AsAcn;
use EastpointLabs\LaravelAu\Casts\AsBsb;
use EastpointLabs\LaravelAu\Casts\AsPostcode;
use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Bsb;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Tests\Casts\Fixtures\TestModel;

mutates(AsAbn::class, AsAcn::class, AsBsb::class, AsPostcode::class);

// Cast, value object, canonical value, a formatted form, an invalid value, the error message,
// and another valid value.
dataset('value object casts', [
    'AsAbn' => [AsAbn::class, Abn::class, '51824753556', '51 824 753 556', '51 824 753 557', 'The given value is not a valid ABN.', '83914571673'],
    'AsAcn' => [AsAcn::class, Acn::class, '004085616', '004 085 616', '000 000 018', 'The given value is not a valid ACN.', '000000019'],
    'AsBsb' => [AsBsb::class, Bsb::class, '062000', '062-000', '06200', 'The given value is not a valid BSB.', '123456'],
    'AsPostcode' => [AsPostcode::class, Postcode::class, '2600', ' 2600 ', '0100', 'The given value is not a valid postcode.', '3000'],
]);

it('reads a stored value as the value object, and null as null', function (
    string $cast,
    string $class,
    string $canonical,
    string $formatted,
): void {
    $stored = TestModel::casting(['field' => $cast])->setRawAttributes(['field' => $canonical]);
    $storedFormatted = TestModel::casting(['field' => $cast])->setRawAttributes(['field' => $formatted]);
    $storedNull = TestModel::casting(['field' => $cast])->setRawAttributes(['field' => null]);

    expect($stored->field)->toBeInstanceOf($class)
        ->and($stored->field->equals($class::from($canonical)))->toBeTrue()
        ->and($storedFormatted->field)->toBeInstanceOf($class)
        ->and($storedFormatted->field->value)->toBe($canonical)
        ->and($storedNull->field)->toBeNull();
})->with('value object casts');

it('stores a value object, a formatted string or null as the canonical value', function (
    string $cast,
    string $class,
    string $canonical,
    string $formatted,
): void {
    $model = TestModel::casting(['field' => $cast]);

    $model->field = $class::from($formatted);

    expect($model->getAttributes()['field'])->toBe($canonical)
        ->and($model->field)->toBeInstanceOf($class);

    $model->field = $formatted;

    expect($model->getAttributes()['field'])->toBe($canonical)
        ->and($model->field)->toBeInstanceOf($class)
        ->and($model->field->value)->toBe($canonical);

    $model->field = null;

    expect($model->getAttributes()['field'])->toBeNull()
        ->and($model->field)->toBeNull();
})->with('value object casts');

it('rejects an invalid value when setting or reading it, without revealing it', function (
    string $cast,
    string $class,
    string $canonical,
    string $formatted,
    string $invalid,
    string $message,
): void {
    $model = TestModel::casting(['field' => $cast]);
    $stored = TestModel::casting(['field' => $cast])->setRawAttributes(['field' => $invalid]);

    $withoutTheInput = function (InvalidValueException $exception) use ($invalid, $message): void {
        expect($exception->getMessage())
            ->toBe($message)
            ->not->toContain($invalid)
            ->not->toMatch('/\d/');
    };

    expect(fn () => $model->field = $invalid)->toThrow($withoutTheInput)
        ->and(fn () => $stored->field)->toThrow($withoutTheInput);
})->with('value object casts');

it('rejects any other type when setting or reading it', function (
    string $cast,
    string $class,
    string $canonical,
    string $formatted,
    string $invalid,
    string $message,
): void {
    $others = [
        'an array' => [$canonical],
        'a float' => (float) $canonical,
        'a boolean' => true,
        'another value object' => PhoneNumber::from('0491 570 006'),
        'an object that renders as the value' => new class($canonical) implements Stringable
        {
            public function __construct(private readonly string $value) {}

            public function __toString(): string
            {
                return $this->value;
            }
        },
    ];

    foreach ($others as $other) {
        $model = TestModel::casting(['field' => $cast]);

        expect(fn () => $model->field = $other)->toThrow(InvalidValueException::class, $message);
    }

    foreach ([[$canonical], (float) $canonical] as $other) {
        $stored = TestModel::casting(['field' => $cast])->setRawAttributes(['field' => $other]);

        expect(fn () => $stored->field)->toThrow(InvalidValueException::class, $message);
    }
})->with('value object casts');

it('serialises to the canonical value, however it was stored, and null as null', function (
    string $cast,
    string $class,
    string $canonical,
    string $formatted,
): void {
    $model = TestModel::casting(['field' => $cast])->setRawAttributes(['field' => $formatted]);
    $empty = TestModel::casting(['field' => $cast])->setRawAttributes(['field' => null]);

    expect($model->toArray())->toBe(['field' => $canonical])
        ->and($model->toJson())->toBe(json_encode(['field' => $canonical]))
        ->and($empty->toArray())->toBe(['field' => null])
        ->and($empty->toJson())->toBe('{"field":null}');
})->with('value object casts');

it('reads and stores a value held as an integer as its digits', function (string $cast, int $integer, string $digits): void {
    $stored = TestModel::casting(['field' => $cast])->setRawAttributes(['field' => $integer]);
    $model = TestModel::casting(['field' => $cast]);

    $model->field = $integer;

    expect($stored->field?->value)->toBe($digits)
        ->and($model->getAttributes()['field'])->toBe($digits);
})->with([
    // An integer column: the value has no leading zero to lose.
    'an ABN' => [AsAbn::class, 51824753556, '51824753556'],
    'an ACN' => [AsAcn::class, 123456780, '123456780'],
    'a BSB' => [AsBsb::class, 123456, '123456'],
    'a postcode' => [AsPostcode::class, 2600, '2600'],
]);

it('rejects an ACN held as an integer, which has lost its leading zeros', function (): void {
    $stored = TestModel::casting(['acn' => AsAcn::class])->setRawAttributes(['acn' => 4085616]);
    $model = TestModel::casting(['acn' => AsAcn::class]);

    $withoutTheInput = function (InvalidValueException $exception): void {
        expect($exception->getMessage())
            ->toBe('The given value is not a valid ACN.')
            ->not->toContain('4085616');
    };

    expect(fn () => $stored->acn)->toThrow($withoutTheInput)
        ->and(fn () => $model->acn = 4085616)->toThrow($withoutTheInput);
});

it('only marks the model as changed when the value itself changes', function (
    string $cast,
    string $class,
    string $canonical,
    string $formatted,
    string $invalid,
    string $message,
    string $another,
): void {
    $model = TestModel::casting(['field' => $cast])->setRawAttributes(['field' => $formatted], sync: true);

    // Reading caches the value object, which Laravel stores again in canonical form.
    expect($model->field)->toBeInstanceOf($class)
        ->and($model->isDirty('field'))->toBeFalse();

    $model->field = $canonical;

    expect($model->isDirty('field'))->toBeFalse();

    $model->field = $another;

    expect($model->isDirty('field'))->toBeTrue();
})->with('value object casts');
