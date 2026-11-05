<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Forms\AuAddressFields;
use EastpointLabs\LaravelAu\Filament\Forms\AuPostcodeInput;
use EastpointLabs\LaravelAu\Filament\Forms\StateSelect;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

mutates(AuAddressFields::class);

it('is a two-column grid of the five address fields, named with the prefix', function (string $prefix): void {
    $grid = AuAddressFields::make($prefix);

    $form = testForm(fn (): array => [AuAddressFields::make($prefix)]);

    $fields = array_map(
        fn (Field $field): array => [$field::class, $field->getLabel(), $field->isRequired()],
        $form->instance()->getSchema('form')?->getFlatFields(withHidden: true) ?? [],
    );

    expect($grid)->toBeInstanceOf(Grid::class)
        ->and($grid->getColumns('lg'))->toBe(2)
        ->and($fields)->toBe([
            "{$prefix}street_line_1" => [TextInput::class, 'Street address', true],
            "{$prefix}street_line_2" => [TextInput::class, 'Address line 2', false],
            "{$prefix}suburb" => [TextInput::class, 'Suburb', true],
            "{$prefix}state" => [StateSelect::class, 'State', true],
            "{$prefix}postcode" => [AuPostcodeInput::class, 'Postcode', true],
        ]);
})->with([
    'a prefix' => 'billing_',
    'no prefix' => '',
]);

it('requires every field but the second address line', function (): void {
    $form = testForm(fn (): array => [AuAddressFields::make('billing_')])
        ->call('save')
        ->assertHasFormErrors([
            'billing_street_line_1' => 'required',
            'billing_suburb' => 'required',
            'billing_state' => 'required',
            'billing_postcode' => 'required',
        ]);

    expect(array_keys($form->errors()->toArray()))->toEqualCanonicalizing([
        'data.billing_street_line_1',
        'data.billing_suburb',
        'data.billing_state',
        'data.billing_postcode',
    ]);
});

it('saves a complete address', function (): void {
    $address = [
        'billing_street_line_1' => '1 Example Street',
        'billing_street_line_2' => 'Level 2',
        'billing_suburb' => 'Melbourne',
        'billing_state' => 'VIC',
        'billing_postcode' => '3000',
    ];

    $form = testForm(fn (): array => [AuAddressFields::make('billing_')])
        ->fillForm($address)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toBe($address);
});

it('rejects a postcode outside the chosen state', function (): void {
    $form = testForm(fn (): array => [AuAddressFields::make('billing_')])
        ->fillForm([
            'billing_street_line_1' => '1 Example Street',
            'billing_suburb' => 'Melbourne',
            'billing_state' => 'NSW',
            'billing_postcode' => '3000',
        ])
        ->call('save');

    expect($form->errors()->toArray())->toBe([
        'data.billing_postcode' => ['The postcode must be in New South Wales.'],
    ]);
});
