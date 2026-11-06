<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Display;
use EastpointLabs\LaravelAu\Filament\Label;
use EastpointLabs\LaravelAu\Filament\Tables\AbnColumn;
use EastpointLabs\LaravelAu\Filament\Tables\AcnColumn;
use EastpointLabs\LaravelAu\Filament\Tables\AuPhoneColumn;
use EastpointLabs\LaravelAu\Filament\Tables\BsbColumn;
use EastpointLabs\LaravelAu\Filament\Tables\StateColumn;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\CastTestRecord;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestRecord;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;

mutates(AbnColumn::class, AcnColumn::class, BsbColumn::class, AuPhoneColumn::class, StateColumn::class, Label::class, Display::class);

// Column, the attribute it shows, a stored value, how it's shown, and a stored value that
// doesn't parse.
dataset('columns', [
    'AbnColumn' => [AbnColumn::class, 'abn', '51824753556', '51 824 753 556', '51824753557'],
    'AcnColumn' => [AcnColumn::class, 'acn', '004085616', '004 085 616', '000 000 018'],
    'BsbColumn' => [BsbColumn::class, 'bsb', '062000', '062-000', '06200'],
    'AuPhoneColumn' => [AuPhoneColumn::class, 'phone', '+61491570156', '0491 570 156', 'not a phone'],
    'StateColumn' => [StateColumn::class, 'state', 'NSW', 'New South Wales', 'XYZ'],
]);

it('is a text column labelled as its form field is, whatever its name, unless given a label', function (): void {
    $table = testTable(fn (): array => [
        AbnColumn::make('supplier_abn'),
        AbnColumn::make('abn')->label('Supplier ABN'),
        AcnColumn::make('company_acn'),
        AcnColumn::make('acn')->label('Company ACN'),
        BsbColumn::make('refund_bsb'),
        BsbColumn::make('bsb')->label('Refund BSB'),
        AuPhoneColumn::make('mobile_number'),
        AuPhoneColumn::make('phone')->label('Mobile'),
        StateColumn::make('billing_state'),
        StateColumn::make('state')->label('Region'),
    ]);

    $columns = $table->instance()->getTable()->getColumns();

    expect($columns)->each->toBeInstanceOf(TextColumn::class);

    expect(array_map(fn (Column $column): mixed => $column->getLabel(), $columns))->toBe([
        'supplier_abn' => 'ABN',
        'abn' => 'Supplier ABN',
        'company_acn' => 'ACN',
        'acn' => 'Company ACN',
        'refund_bsb' => 'BSB',
        'bsb' => 'Refund BSB',
        'mobile_number' => 'Phone',
        'phone' => 'Mobile',
        'billing_state' => 'State',
        'state' => 'Region',
    ]);
});

it('shows a stored value formatted, whether it is read as a string or through a cast', function (
    string $column,
    string $attribute,
    string $stored,
    string $shown,
): void {
    $record = TestRecord::create([$attribute => $stored]);

    // The cast gives the column a value object or a State rather than the stored string.
    expect(CastTestRecord::of($record)->{$attribute})->toBeObject();

    testTable(fn (): array => [$column::make($attribute)])
        ->assertTableColumnFormattedStateSet($attribute, $shown, $record)
        ->assertSee($shown);

    testTable(fn (): array => [$column::make($attribute)], CastTestRecord::class)
        ->assertTableColumnFormattedStateSet($attribute, $shown, $record)
        ->assertSee($shown);
})->with('columns');

it('shows a stored value it cannot read as it is stored, and nothing for no value', function (
    string $column,
    string $attribute,
    string $stored,
    string $shown,
    string $unreadable,
): void {
    $record = TestRecord::create([$attribute => $unreadable]);
    TestRecord::create([$attribute => null]);

    testTable(fn (): array => [$column::make($attribute)->placeholder('Not recorded')])
        ->assertTableColumnFormattedStateSet($attribute, $unreadable, $record)
        ->assertSee($unreadable)
        ->assertSee('Not recorded');
})->with('columns');

it('formats a value read from an integer column', function (): void {
    // An integer column has no leading zero to lose for an ABN.
    expect(AbnColumn::make('abn')->formatState(51824753556))->toBe('51 824 753 556');
});
