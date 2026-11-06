<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Concerns\CanBeDialled;
use EastpointLabs\LaravelAu\Filament\Tables\AuPhoneColumn;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\CastTestRecord;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestRecord;

mutates(AuPhoneColumn::class, CanBeDialled::class);

it('links to the number to dial it, in the same tab, with dialable()', function (
    string $recordClass,
    string $stored,
    string $shown,
    string $href,
): void {
    $column = AuPhoneColumn::make('phone');

    expect($column->dialable())->toBe($column);

    $record = TestRecord::create(['phone' => $stored]);

    testTable(fn (): array => [AuPhoneColumn::make('phone')->dialable()], $recordClass)
        ->assertTableColumnFormattedStateSet('phone', $shown, $record)
        ->assertSeeHtml("href=\"{$href}\"")
        ->assertDontSeeHtml('target="_blank"');
})->with([
    'a mobile stored as national digits' => [TestRecord::class, '0491570156', '0491 570 156', 'tel:+61491570156'],
    'a mobile read as a PhoneNumber through the cast' => [CastTestRecord::class, '+61491570156', '0491 570 156', 'tel:+61491570156'],
    'a service number, which has no E.164 form' => [TestRecord::class, '1300975707', '1300 975 707', 'tel:1300975707'],
]);

it('does not link a number without dialable(), or a stored value that is not a phone number', function (Closure $column, string $stored): void {
    TestRecord::create(['phone' => $stored]);

    testTable(fn (): array => [$column()])
        ->assertDontSeeHtml('tel:');
})->with([
    'without dialable()' => [fn (): AuPhoneColumn => AuPhoneColumn::make('phone'), '+61491570156'],
    'a stored value that is not a phone number' => [fn (): AuPhoneColumn => AuPhoneColumn::make('phone')->dialable(), 'not a phone'],
]);
