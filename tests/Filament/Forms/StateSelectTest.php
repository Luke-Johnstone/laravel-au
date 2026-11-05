<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Forms\StateSelect;
use EastpointLabs\LaravelAu\Support\State;
use Filament\Forms\Components\Select;
use Illuminate\Validation\Rule;

mutates(StateSelect::class);

it('is a select of the eight states and territories, labelled State, whatever its name', function (): void {
    $field = formField(testForm(fn (): array => [StateSelect::make('billing_state')]), 'billing_state');

    expect($field)->toBeInstanceOf(Select::class)
        ->and($field->getLabel())->toBe('State')
        ->and($field->getOptions())->toBe(State::options())->toHaveCount(8)
        // Select already rejects values that aren't options; the enum rule still holds if
        // the options are changed.
        ->and($field->getValidationRules())->toContainEqual(Rule::enum(State::class));
});

it('saves the code of the chosen state', function (): void {
    $form = testForm(fn (): array => [StateSelect::make('state')])
        ->fillForm(['state' => 'ACT'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toBe(['state' => 'ACT']);
});

it('saves several codes from a multiple select', function (): void {
    $form = testForm(fn (): array => [StateSelect::make('states')->multiple()])
        ->fillForm(['states' => ['NSW', 'VIC']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->get('saved'))->toBe(['states' => ['NSW', 'VIC']]);
});

it('rejects a value that is not a state code', function (): void {
    $form = testForm(fn (): array => [StateSelect::make('state')])
        ->fillForm(['state' => 'XYZ'])
        ->call('save');

    expect($form->errors()->get('data.state'))->toBe(['The selected state is invalid.'])
        ->and($form->get('saved'))->toBeNull();
});
