<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Forms\AcnInput;
use Filament\Forms\Components\TextInput;

mutates(AcnInput::class);

it('is a masked text input labelled ACN, whatever its name', function (): void {
    $field = formField(testForm(fn (): array => [AcnInput::make('company_acn')]), 'company_acn');

    expect($field)->toBeInstanceOf(TextInput::class)
        ->and($field->getLabel())->toBe('ACN')
        ->and($field->getMask())->toBe('999 999 999');
});

it('rejects an invalid ACN, naming the field by its label as written', function (?string $label, string $message): void {
    $form = testForm(fn (): array => [$label === null ? AcnInput::make('acn') : AcnInput::make('acn')->label($label)])
        ->fillForm(['acn' => '000 000 018'])
        ->call('save');

    expect(formField($form, 'acn')->getLabel())->toBe($label ?? 'ACN')
        ->and($form->errors()->get('data.acn'))->toBe([$message])
        ->and($form->get('saved'))->toBeNull();
})->with([
    'the default label' => [null, 'The ACN must be a valid ACN.'],
    'a label of your own' => ['Company ACN', 'The Company ACN must be a valid ACN.'],
]);

it('saves a valid ACN as its 9 digits, keeping leading zeros', function (): void {
    $form = testForm(fn (): array => [AcnInput::make('acn')])
        ->fillForm(['acn' => '004 085 616'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toBe(['acn' => '004085616']);
});

it('shows a stored ACN formatted, and saves it back as digits', function (): void {
    $form = testForm(fn (): array => [AcnInput::make('acn')], ['acn' => '004085616'])
        ->assertSchemaStateSet(['acn' => '004 085 616'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toBe(['acn' => '004085616']);
});

it('shows a stored value that is not a valid ACN as it is', function (): void {
    testForm(fn (): array => [AcnInput::make('acn')], ['acn' => '000000018'])
        ->assertSchemaStateSet(['acn' => '000000018']);
});

it('saves an empty optional field as empty', function (): void {
    $form = testForm(fn (): array => [AcnInput::make('acn')])
        ->fillForm(['acn' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toHaveKey('acn')
        ->and($form->get('saved.acn'))->toBeEmpty();
});
