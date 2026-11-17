<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Casts\AsPhoneNumber;
use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Tests\Casts\Fixtures\TestModel;

mutates(AsPhoneNumber::class);

// Input, what's stored by default (E.164), what's stored when set to national digits.
dataset('phone numbers to store', [
    'a mobile' => ['0491 570 006', '+61491570006', '0491570006'],
    'a landline' => ['(02) 5550 1234', '+61255501234', '0255501234'],
    'a service number, which has no E.164 form' => ['1300 975 707', '1300975707', '1300975707'],
    'a phone number object' => [PhoneNumber::from('+61 491 570 006'), '+61491570006', '0491570006'],
]);

it('stores and serialises E.164 by default, and national digits when configured', function (
    string|PhoneNumber $input,
    string $byDefault,
    string $national,
): void {
    $model = TestModel::casting(['phone' => AsPhoneNumber::class]);
    $model->phone = $input;

    expect($model->getAttributes()['phone'])->toBe($byDefault)
        ->and($model->toArray())->toBe(['phone' => $byDefault])
        ->and($model->toJson())->toBe(json_encode(['phone' => $byDefault]))
        ->and($model->phone)->toBeInstanceOf(PhoneNumber::class)
        ->and($model->phone->value)->toBe($national);

    config(['au.phone.store_as' => 'national']);

    $model = TestModel::casting(['phone' => AsPhoneNumber::class]);
    $model->phone = $input;

    expect($model->getAttributes()['phone'])->toBe($national)
        ->and($model->toArray())->toBe(['phone' => $national])
        ->and($model->toJson())->toBe(json_encode(['phone' => $national]))
        ->and($model->phone)->toBeInstanceOf(PhoneNumber::class)
        ->and($model->phone->value)->toBe($national);
})->with('phone numbers to store');

it('reads a number stored in either form as the same phone number', function (): void {
    $e164 = TestModel::casting(['phone' => AsPhoneNumber::class])->setRawAttributes(['phone' => '+61491570006']);
    $national = TestModel::casting(['phone' => AsPhoneNumber::class])->setRawAttributes(['phone' => '0491570006']);

    expect($e164->phone)->toBeInstanceOf(PhoneNumber::class)
        ->and($e164->phone->value)->toBe('0491570006')
        ->and($national->phone)->toBeInstanceOf(PhoneNumber::class)
        ->and($national->phone->value)->toBe('0491570006');
});

it('follows the setting in force when storing and when serialising', function (): void {
    $model = TestModel::casting(['phone' => AsPhoneNumber::class]);
    $model->phone = '0491 570 006';

    config(['au.phone.store_as' => 'national']);

    expect($model->getAttributes()['phone'])->toBe('+61491570006')
        ->and($model->toArray())->toBe(['phone' => '0491570006'])
        ->and($model->phone->value)->toBe('0491570006');

    $model->phone = '0491 570 006';

    expect($model->getAttributes()['phone'])->toBe('0491570006');
});

it('stores, reads and serialises null as null', function (): void {
    $model = TestModel::casting(['phone' => AsPhoneNumber::class])->setRawAttributes(['phone' => '0491570006']);

    $model->phone = null;

    expect($model->getAttributes()['phone'])->toBeNull()
        ->and($model->phone)->toBeNull()
        ->and($model->toArray())->toBe(['phone' => null])
        ->and($model->toJson())->toBe('{"phone":null}');
});

it('reads and stores a number held as an integer as its digits', function (): void {
    $stored = TestModel::casting(['phone' => AsPhoneNumber::class])->setRawAttributes(['phone' => 1300975707]);
    $model = TestModel::casting(['phone' => AsPhoneNumber::class]);

    $model->phone = 1300975707;

    expect($stored->phone)->toBeInstanceOf(PhoneNumber::class)
        ->and($stored->phone->value)->toBe('1300975707')
        ->and($model->getAttributes()['phone'])->toBe('1300975707');
});

it('rejects an invalid number when setting or reading it, without revealing it', function (): void {
    $model = TestModel::casting(['phone' => AsPhoneNumber::class]);
    $stored = TestModel::casting(['phone' => AsPhoneNumber::class])->setRawAttributes(['phone' => '049157000']);

    $withoutTheInput = function (InvalidValueException $exception): void {
        expect($exception->getMessage())
            ->toBe('The given value is not a valid phone number.')
            ->not->toContain('049157000')
            ->not->toMatch('/\d/');
    };

    expect(fn () => $model->phone = '0491 570 00')->toThrow($withoutTheInput)
        ->and(fn () => $stored->phone)->toThrow($withoutTheInput);
});

it('rejects any other type when setting or reading it', function (): void {
    $others = [
        'an array' => ['0491570006'],
        'a float' => 61491570006.0,
        'a boolean' => true,
        'another value object' => Abn::from('51 824 753 556'),
        'an object that renders as a number' => new class implements Stringable
        {
            public function __toString(): string
            {
                return '0491570006';
            }
        },
    ];

    foreach ($others as $other) {
        $model = TestModel::casting(['phone' => AsPhoneNumber::class]);

        expect(fn () => $model->phone = $other)->toThrow(InvalidValueException::class, 'The given value is not a valid phone number.');
    }

    foreach ([['0491570006'], 61491570006.0] as $other) {
        $stored = TestModel::casting(['phone' => AsPhoneNumber::class])->setRawAttributes(['phone' => $other]);

        expect(fn () => $stored->phone)->toThrow(InvalidValueException::class, 'The given value is not a valid phone number.');
    }
});

it('only marks the model as changed when the number itself changes', function (): void {
    $model = TestModel::casting(['phone' => AsPhoneNumber::class])->setRawAttributes(['phone' => '0491570006'], sync: true);

    // Stored as national digits; the default setting stores E.164.
    expect($model->phone)->toBeInstanceOf(PhoneNumber::class)
        ->and($model->isDirty('phone'))->toBeFalse();

    $model->phone = '+61 491 570 006';

    expect($model->isDirty('phone'))->toBeFalse();

    $model->phone = '0491 570 156';

    expect($model->isDirty('phone'))->toBeTrue();

    // Giving an empty column a number is a change too.
    $empty = TestModel::casting(['phone' => AsPhoneNumber::class])->setRawAttributes(['phone' => null], sync: true);
    $empty->phone = '0491570006';

    expect($empty->isDirty('phone'))->toBeTrue();
});
