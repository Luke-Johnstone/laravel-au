<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Concerns\CanBeAbbreviated;
use EastpointLabs\LaravelAu\Filament\Infolists\StateEntry;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\CastTestRecord;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestRecord;

mutates(StateEntry::class, CanBeAbbreviated::class);

it('shows the code rather than the name with abbreviated()', function (Closure $record, string $shown): void {
    $entry = StateEntry::make('state');

    expect($entry->abbreviated())->toBe($entry);

    $infolist = testInfolist(fn (): array => [StateEntry::make('state')->abbreviated()], $record())
        ->assertSee($shown)
        ->assertDontSee('New South Wales');

    expect(formattedEntryState($infolist, 'state'))->toBe($shown);
})->with([
    'a stored code' => [fn (): TestRecord => TestRecord::create(['state' => 'NSW']), 'NSW'],
    'a State read through the enum cast' => [fn (): CastTestRecord => CastTestRecord::of(TestRecord::create(['state' => 'NSW'])), 'NSW'],
    'a stored value that is not a state' => [fn (): TestRecord => TestRecord::create(['state' => 'XYZ']), 'XYZ'],
]);
