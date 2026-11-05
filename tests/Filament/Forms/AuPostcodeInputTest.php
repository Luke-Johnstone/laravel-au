<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Forms\AuPostcodeInput;
use EastpointLabs\LaravelAu\Filament\Forms\StateSelect;
use Filament\Forms\Components\TextInput;

mutates(AuPostcodeInput::class);

it('is a 4-character text input with a numeric keyboard, labelled Postcode, whatever its name', function (): void {
    $field = formField(testForm(fn (): array => [AuPostcodeInput::make('delivery_postcode')]), 'delivery_postcode');

    expect($field)->toBeInstanceOf(TextInput::class)
        ->and($field->getLabel())->toBe('Postcode')
        ->and($field->getMaxLength())->toBe(4)
        ->and($field->getInputMode())->toBe('numeric')
        ->and($field->getType())->toBe('text');
});

it('rejects a postcode that is in no state', function (): void {
    $form = testForm(fn (): array => [AuPostcodeInput::make('postcode')])
        ->fillForm(['postcode' => '0100'])
        ->call('save');

    expect($form->errors()->get('data.postcode'))->toBe(['The postcode must be a valid Australian postcode.'])
        ->and($form->get('saved'))->toBeNull();
});

describe('forState()', function (): void {
    it('checks the postcode against the state chosen in the other field', function (?string $state, array $errors): void {
        $form = testForm(fn (): array => [
            StateSelect::make('state'),
            AuPostcodeInput::make('postcode')->forState('state'),
        ])
            ->fillForm(['state' => $state, 'postcode' => '3000'])
            ->call('save');

        expect($form->errors()->toArray())->toBe($errors);
    })->with([
        'a state the postcode is not in' => ['NSW', ['data.postcode' => ['The postcode must be in New South Wales.']]],
        'the state the postcode is in' => ['VIC', []],
        'no state' => [null, []],
        'an unknown state, which only the state field reports' => ['XYZ', ['data.state' => ['The selected state is invalid.']]],
    ]);

    it('saves a postcode with a leading zero in its state', function (): void {
        $form = testForm(fn (): array => [
            StateSelect::make('state'),
            AuPostcodeInput::make('postcode')->forState('state'),
        ])
            ->fillForm(['state' => 'NT', 'postcode' => '0800'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($form->get('saved'))->toBe(['state' => 'NT', 'postcode' => '0800']);
    });
});
