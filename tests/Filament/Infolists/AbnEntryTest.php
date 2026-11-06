<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Concerns\LinksToAbnLookup;
use EastpointLabs\LaravelAu\Filament\Infolists\AbnEntry;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\CastTestRecord;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestRecord;

mutates(AbnEntry::class, LinksToAbnLookup::class);

it('links to the ABN on ABN Lookup in a new tab with linkToAbnLookup()', function (Closure $record): void {
    $entry = AbnEntry::make('abn');

    expect($entry->linkToAbnLookup())->toBe($entry);

    $infolist = testInfolist(fn (): array => [AbnEntry::make('abn')->linkToAbnLookup()], $record())
        ->assertSeeHtml('href="https://abr.business.gov.au/ABN/View?abn=51824753556" target="_blank"');

    expect(formattedEntryState($infolist, 'abn'))->toBe('51 824 753 556');
})->with([
    'stored as a formatted string' => [fn (): TestRecord => TestRecord::create(['abn' => '51 824 753 556'])],
    'read as an Abn through the cast' => [fn (): CastTestRecord => CastTestRecord::of(TestRecord::create(['abn' => '51824753556']))],
]);

it('does not link to ABN Lookup without linkToAbnLookup(), or for a stored value that is not an ABN', function (Closure $entry, string $stored): void {
    testInfolist(fn (): array => [$entry()], TestRecord::create(['abn' => $stored]))
        ->assertDontSeeHtml('abr.business.gov.au')
        ->assertDontSeeHtml('target="_blank"');
})->with([
    'without linkToAbnLookup()' => [fn (): AbnEntry => AbnEntry::make('abn'), '51824753556'],
    'a stored value that is not an ABN' => [fn (): AbnEntry => AbnEntry::make('abn')->linkToAbnLookup(), '51824753557'],
]);
