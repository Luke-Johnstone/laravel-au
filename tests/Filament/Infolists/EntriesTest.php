<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Display;
use EastpointLabs\LaravelAu\Filament\Infolists\AbnEntry;
use EastpointLabs\LaravelAu\Filament\Infolists\AcnEntry;
use EastpointLabs\LaravelAu\Filament\Infolists\AuPhoneEntry;
use EastpointLabs\LaravelAu\Filament\Infolists\BsbEntry;
use EastpointLabs\LaravelAu\Filament\Infolists\StateEntry;
use EastpointLabs\LaravelAu\Filament\Label;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\CastTestRecord;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestRecord;
use Filament\Infolists\Components\TextEntry;

mutates(AbnEntry::class, AcnEntry::class, BsbEntry::class, AuPhoneEntry::class, StateEntry::class, Label::class, Display::class);

// Entry, the attribute it shows, a stored value, how it's shown, and a stored value that
// doesn't parse.
dataset('entries', [
    'AbnEntry' => [AbnEntry::class, 'abn', '51824753556', '51 824 753 556', '51824753557'],
    'AcnEntry' => [AcnEntry::class, 'acn', '004085616', '004 085 616', '000 000 018'],
    'BsbEntry' => [BsbEntry::class, 'bsb', '062000', '062-000', '06200'],
    'AuPhoneEntry' => [AuPhoneEntry::class, 'phone', '+61491570156', '0491 570 156', 'not a phone'],
    'StateEntry' => [StateEntry::class, 'state', 'NSW', 'New South Wales', 'XYZ'],
]);

it('is a text entry labelled as its form field is, whatever its name, unless given a label', function (): void {
    $infolist = testInfolist(fn (): array => [
        AbnEntry::make('supplier_abn'),
        AbnEntry::make('abn')->label('Supplier ABN'),
        AcnEntry::make('company_acn'),
        AcnEntry::make('acn')->label('Company ACN'),
        BsbEntry::make('refund_bsb'),
        BsbEntry::make('bsb')->label('Refund BSB'),
        AuPhoneEntry::make('mobile_number'),
        AuPhoneEntry::make('phone')->label('Mobile'),
        StateEntry::make('billing_state'),
        StateEntry::make('state')->label('Region'),
    ], TestRecord::create());

    $entries = $infolist->instance()->getSchema('infolist')?->getFlatComponents(withHidden: true);

    expect($entries)->each->toBeInstanceOf(TextEntry::class);

    expect(array_map(fn (TextEntry $entry): mixed => $entry->getLabel(), $entries))->toBe([
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
    string $entry,
    string $attribute,
    string $stored,
    string $shown,
): void {
    $record = TestRecord::create([$attribute => $stored]);
    $castRecord = CastTestRecord::of($record);

    // The cast gives the entry a value object or a State rather than the stored string.
    expect($castRecord->{$attribute})->toBeObject();

    $infolist = testInfolist(fn (): array => [$entry::make($attribute)], $record)->assertSee($shown);

    expect(formattedEntryState($infolist, $attribute))->toBe($shown);

    $infolist = testInfolist(fn (): array => [$entry::make($attribute)], $castRecord)->assertSee($shown);

    expect(formattedEntryState($infolist, $attribute))->toBe($shown);
})->with('entries');

it('shows a stored value it cannot read as it is stored, and nothing for no value', function (
    string $entry,
    string $attribute,
    string $stored,
    string $shown,
    string $unreadable,
): void {
    $infolist = testInfolist(
        fn (): array => [$entry::make($attribute)->placeholder('Not recorded')],
        TestRecord::create([$attribute => $unreadable]),
    )
        ->assertSee($unreadable)
        ->assertDontSee('Not recorded');

    expect(formattedEntryState($infolist, $attribute))->toBe($unreadable);

    testInfolist(
        fn (): array => [$entry::make($attribute)->placeholder('Not recorded')],
        TestRecord::create([$attribute => null]),
    )
        ->assertSee('Not recorded');
})->with('entries');
