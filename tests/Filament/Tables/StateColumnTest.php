<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Concerns\CanBeAbbreviated;
use EastpointLabs\LaravelAu\Filament\Tables\StateColumn;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\CastTestRecord;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestRecord;

mutates(StateColumn::class, CanBeAbbreviated::class);

it('shows the code rather than the name with abbreviated()', function (string $recordClass, string $stored, string $shown): void {
    $column = StateColumn::make('state');

    expect($column->abbreviated())->toBe($column);

    $record = TestRecord::create(['state' => $stored]);

    testTable(fn (): array => [StateColumn::make('state')->abbreviated()], $recordClass)
        ->assertTableColumnFormattedStateSet('state', $shown, $record)
        ->assertSee($shown)
        ->assertDontSee('New South Wales');
})->with([
    'a stored code' => [TestRecord::class, 'NSW', 'NSW'],
    'a State read through the enum cast' => [CastTestRecord::class, 'NSW', 'NSW'],
    'a stored value that is not a state' => [TestRecord::class, 'XYZ', 'XYZ'],
]);
