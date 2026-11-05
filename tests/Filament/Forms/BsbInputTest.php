<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Forms\BsbInput;
use Filament\Forms\Components\TextInput;

mutates(BsbInput::class);

it('is a masked text input labelled BSB, whatever its name', function (): void {
    $field = formField(testForm(fn (): array => [BsbInput::make('payee_bsb')]), 'payee_bsb');

    expect($field)->toBeInstanceOf(TextInput::class)
        ->and($field->getLabel())->toBe('BSB')
        ->and($field->getMask())->toBe('999-999');
});

it('rejects a BSB without 6 digits, naming the field by its label as written', function (?string $label, string $message): void {
    $form = testForm(fn (): array => [$label === null ? BsbInput::make('bsb') : BsbInput::make('bsb')->label($label)])
        ->fillForm(['bsb' => '062-00'])
        ->call('save');

    expect(formField($form, 'bsb')->getLabel())->toBe($label ?? 'BSB')
        ->and($form->errors()->get('data.bsb'))->toBe([$message])
        ->and($form->get('saved'))->toBeNull();
})->with([
    'the default label' => [null, 'The BSB must be a valid BSB.'],
    'a label of your own' => ['Payee BSB', 'The Payee BSB must be a valid BSB.'],
]);

it('saves a valid BSB as its 6 digits, keeping leading zeros', function (): void {
    $form = testForm(fn (): array => [BsbInput::make('bsb')])
        ->fillForm(['bsb' => '062-000'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toBe(['bsb' => '062000']);
});

it('shows a stored BSB formatted, and saves it back as digits', function (): void {
    $form = testForm(fn (): array => [BsbInput::make('bsb')], ['bsb' => '062000'])
        ->assertSchemaStateSet(['bsb' => '062-000'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toBe(['bsb' => '062000']);
});

it('shows a stored value that is not a valid BSB as it is', function (): void {
    testForm(fn (): array => [BsbInput::make('bsb')], ['bsb' => '06200'])
        ->assertSchemaStateSet(['bsb' => '06200']);
});

it('saves an empty optional field as empty', function (): void {
    $form = testForm(fn (): array => [BsbInput::make('bsb')])
        ->fillForm(['bsb' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toHaveKey('bsb')
        ->and($form->get('saved.bsb'))->toBeEmpty();
});
