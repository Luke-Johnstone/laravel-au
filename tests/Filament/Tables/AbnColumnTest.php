<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Filament\Concerns\LinksToAbnLookup;
use EastpointLabs\LaravelAu\Filament\Tables\AbnColumn;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\CastTestRecord;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestRecord;

mutates(AbnColumn::class, LinksToAbnLookup::class);

it('links to the ABN on ABN Lookup in a new tab with linkToAbnLookup()', function (string $recordClass, string $stored): void {
    $column = AbnColumn::make('abn');

    expect($column->linkToAbnLookup())->toBe($column);

    $record = TestRecord::create(['abn' => $stored]);

    testTable(fn (): array => [AbnColumn::make('abn')->linkToAbnLookup()], $recordClass)
        ->assertTableColumnFormattedStateSet('abn', '51 824 753 556', $record)
        ->assertSeeHtml('href="https://abr.business.gov.au/ABN/View?abn=51824753556" target="_blank"');
})->with([
    'stored as a formatted string' => [TestRecord::class, '51 824 753 556'],
    'read as an Abn through the cast' => [CastTestRecord::class, '51824753556'],
]);

it('does not link to ABN Lookup without linkToAbnLookup(), or for a stored value that is not an ABN', function (Closure $column, string $stored): void {
    TestRecord::create(['abn' => $stored]);

    testTable(fn (): array => [$column()])
        ->assertDontSeeHtml('abr.business.gov.au')
        ->assertDontSeeHtml('target="_blank"');
})->with([
    'without linkToAbnLookup()' => [fn (): AbnColumn => AbnColumn::make('abn'), '51824753556'],
    'a stored value that is not an ABN' => [fn (): AbnColumn => AbnColumn::make('abn')->linkToAbnLookup(), '51824753557'],
]);
