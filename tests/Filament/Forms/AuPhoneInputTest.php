<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Forms\AuPhoneInput;
use Filament\Forms\Components\TextInput;

mutates(AuPhoneInput::class);

it('is a telephone input labelled Phone, whatever its name', function (): void {
    $field = formField(testForm(fn (): array => [AuPhoneInput::make('mobile_number')]), 'mobile_number');

    expect($field)->toBeInstanceOf(TextInput::class)
        ->and($field->getLabel())->toBe('Phone')
        ->and($field->getType())->toBe('tel');
});

it('rejects a value that is not a phone number with only the phone message', function (): void {
    $form = testForm(fn (): array => [AuPhoneInput::make('phone')])
        ->fillForm(['phone' => 'call me'])
        ->call('save');

    expect($form->errors()->get('data.phone'))->toBe(['The phone must be a valid Australian phone number.'])
        ->and($form->get('saved'))->toBeNull();
});

it('accepts only the types of number it allows', function (Closure $field, array $accepted): void {
    $numbers = [
        'mobile' => '0491 570 006',
        'landline' => '(02) 5550 1234',
        'service' => '1300 975 707',
    ];

    $results = array_map(
        fn (string $number): bool => testForm(fn (): array => [$field()])
            ->fillForm(['phone' => $number])
            ->call('save')
            ->errors()
            ->has('data.phone') === false,
        $numbers,
    );

    expect($results)->toBe($accepted);
})->with([
    'by default' => [
        fn (): AuPhoneInput => AuPhoneInput::make('phone'),
        ['mobile' => true, 'landline' => true, 'service' => false],
    ],
    'mobileOnly()' => [
        fn (): AuPhoneInput => AuPhoneInput::make('phone')->mobileOnly(),
        ['mobile' => true, 'landline' => false, 'service' => false],
    ],
    'landlineOnly()' => [
        fn (): AuPhoneInput => AuPhoneInput::make('phone')->landlineOnly(),
        ['mobile' => false, 'landline' => true, 'service' => false],
    ],
    'allowServiceNumbers()' => [
        fn (): AuPhoneInput => AuPhoneInput::make('phone')->allowServiceNumbers(),
        ['mobile' => true, 'landline' => true, 'service' => true],
    ],
    'mobileOnly()->allowServiceNumbers()' => [
        fn (): AuPhoneInput => AuPhoneInput::make('phone')->mobileOnly()->allowServiceNumbers(),
        ['mobile' => true, 'landline' => false, 'service' => true],
    ],
    'allowServiceNumbers()->mobileOnly()' => [
        fn (): AuPhoneInput => AuPhoneInput::make('phone')->allowServiceNumbers()->mobileOnly(),
        ['mobile' => true, 'landline' => false, 'service' => true],
    ],
]);

it('saves a number in E.164 form by default', function (string $input, string $saved): void {
    $form = testForm(fn (): array => [AuPhoneInput::make('phone')])
        ->fillForm(['phone' => $input])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toBe(['phone' => $saved]);
})->with([
    'a mobile' => ['0491 570 006', '+61491570006'],
    'a landline' => ['(02) 5550 1234', '+61255501234'],
]);

it('saves national digits when au.phone.store_as is national', function (): void {
    config(['au.phone.store_as' => 'national']);

    $form = testForm(fn (): array => [AuPhoneInput::make('phone')])
        ->fillForm(['phone' => '0491 570 006'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toBe(['phone' => '0491570006']);
});

it('saves a service number as national digits, having no E.164 form', function (string $storeAs): void {
    config(['au.phone.store_as' => $storeAs]);

    $form = testForm(fn (): array => [AuPhoneInput::make('phone')->allowServiceNumbers()])
        ->fillForm(['phone' => '1300 975 707'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toBe(['phone' => '1300975707']);
})->with([
    'e164',
    'national',
]);

it('shows a stored number formatted, and saves it back unchanged', function (): void {
    $form = testForm(fn (): array => [AuPhoneInput::make('phone')], ['phone' => '+61491570006'])
        ->assertSchemaStateSet(['phone' => '0491 570 006'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toBe(['phone' => '+61491570006']);
});

it('saves an empty optional field as empty', function (): void {
    $form = testForm(fn (): array => [AuPhoneInput::make('phone')])
        ->fillForm(['phone' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toHaveKey('phone')
        ->and($form->get('saved.phone'))->toBeEmpty();
});
