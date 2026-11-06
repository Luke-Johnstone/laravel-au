<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Concerns\CanBeDialled;
use EastpointLabs\LaravelAu\Filament\Infolists\AuPhoneEntry;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\CastTestRecord;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestRecord;

mutates(AuPhoneEntry::class, CanBeDialled::class);

it('links to the number to dial it, in the same tab, with dialable()', function (Closure $record, string $shown, string $href): void {
    $entry = AuPhoneEntry::make('phone');

    expect($entry->dialable())->toBe($entry);

    $infolist = testInfolist(fn (): array => [AuPhoneEntry::make('phone')->dialable()], $record())
        ->assertSeeHtml("href=\"{$href}\"")
        ->assertDontSeeHtml('target="_blank"');

    expect(formattedEntryState($infolist, 'phone'))->toBe($shown);
})->with([
    'a mobile stored as national digits' => [
        fn (): TestRecord => TestRecord::create(['phone' => '0491570156']),
        '0491 570 156',
        'tel:+61491570156',
    ],
    'a mobile read as a PhoneNumber through the cast' => [
        fn (): CastTestRecord => CastTestRecord::of(TestRecord::create(['phone' => '+61491570156'])),
        '0491 570 156',
        'tel:+61491570156',
    ],
    'a service number, which has no E.164 form' => [
        fn (): TestRecord => TestRecord::create(['phone' => '1300975707']),
        '1300 975 707',
        'tel:1300975707',
    ],
]);

it('does not link a number without dialable(), or a stored value that is not a phone number', function (Closure $entry, string $stored): void {
    testInfolist(fn (): array => [$entry()], TestRecord::create(['phone' => $stored]))
        ->assertDontSeeHtml('tel:');
})->with([
    'without dialable()' => [fn (): AuPhoneEntry => AuPhoneEntry::make('phone'), '+61491570156'],
    'a stored value that is not a phone number' => [fn (): AuPhoneEntry => AuPhoneEntry::make('phone')->dialable(), 'not a phone'],
]);
