<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\JsonpDecoder;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestForm;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestInfolist;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestRecord;
use EastpointLabs\LaravelAu\Tests\Filament\Fixtures\TestTable;
use EastpointLabs\LaravelAu\Tests\TestCase;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

pest()->extend(TestCase::class)->in(__DIR__);

// CI also runs the suite without Filament installed, excluding this group.
pest()->group('filament')->in('Filament');

// Every Filament test gives its form, table or infolist its own components.
pest()->beforeEach(function (): void {
    TestForm::forgetComponents();
    TestTable::forgetColumns();
    TestInfolist::forgetComponents();
})->in('Filament');

// Tables and infolists show records from Testbench's in-memory SQLite database.
pest()->beforeEach(fn () => TestRecord::createTable())->in('Filament/Tables', 'Filament/Infolists');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Read a file from tests/Fixtures. Pest's own fixture() returns only the path.
 */
function fixtureContents(string $path): string
{
    $contents = file_get_contents(fixture($path));

    if ($contents === false) {
        throw new RuntimeException("Fixture [{$path}] could not be read.");
    }

    return $contents;
}

/**
 * Decode an ABN Lookup JSONP fixture from tests/Fixtures/abr into its payload, e.g.
 * abrPayload('abn-active.jsonp').
 *
 * @return array<mixed>
 */
function abrPayload(string $fixture): array
{
    return JsonpDecoder::decode(fixtureContents("abr/{$fixture}"));
}

/**
 * Assert that an exception message gives nothing away about the ABN Lookup credentials: no
 * GUID (the test GUID or anything shaped like one) and no request URL, which would carry it.
 */
function expectNoGuidOrUrl(string $message): void
{
    expect($message)
        ->not->toBe('')
        ->not->toContain('testing-guid')
        ->not->toMatch('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i')
        ->not->toContain('://')
        ->not->toContain('abr.business.gov.au')
        ->not->toContain('guid=');
}

/**
 * Assert that no frame in the package's part of the stack trace was given the test GUID. The
 * trace only has arguments while zend.exception_ignore_args is off, so that's checked too.
 */
function expectNoGuidInTrace(Throwable $exception): void
{
    $frames = array_filter(
        $exception->getTrace(),
        fn (array $frame): bool => str_starts_with($frame['class'] ?? '', 'EastpointLabs\\LaravelAu\\'),
    );

    expect(array_merge(...array_column($frames, 'args')))->not->toBeEmpty()
        ->and(print_r($frames, true))->not->toContain('testing-guid')
        ->and($exception->getTraceAsString())->not->toContain('testing-guid');
}

/**
 * Assert that exactly one HTTP request was sent: a GET to the given URL (without its query
 * string) carrying exactly the given query parameters, in any order.
 *
 * @param  array<string, string>  $query
 */
function expectOneGetRequest(string $url, array $query): void
{
    Http::assertSentCount(1);

    Http::assertSent(function (Request $request) use ($url, $query): bool {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $sent);

        ksort($sent);
        ksort($query);

        expect($request->method())->toBe('GET')
            ->and(explode('?', $request->url(), 2)[0])->toBe($url)
            ->and($sent)->toBe($query);

        return true;
    });
}

/**
 * Run a rule object on its own and report whether it failed. The validator never runs a rule
 * that isn't implicit on an empty or whitespace-only string, so this is how to check what the
 * rule itself makes of one.
 */
function ruleFails(ValidationRule $rule, mixed $value): bool
{
    $failed = false;

    $rule->validate('field', $value, function (string $attributeOrMessage, ?string $message = null) use (&$failed): PotentiallyTranslatedString {
        $failed = true;

        return new PotentiallyTranslatedString($message ?? $attributeOrMessage, app('translator'));
    });

    return $failed;
}

/**
 * The smallest payload a lookup can return, with one field replaced or added.
 *
 * @param  array<string, mixed>  $fields
 * @return array<string, mixed>
 */
function minimalAbnPayload(array $fields = []): array
{
    return [...['Abn' => '51824753556', 'AbnStatus' => 'Active'], ...$fields];
}

/**
 * A complete name search entry for a valid ABN, with some fields replaced.
 *
 * @param  array<string, mixed>  $fields
 * @return array<string, mixed>
 */
function nameSearchEntry(array $fields = []): array
{
    return [...[
        'Abn' => '51824753556',
        'AbnStatus' => '0000000001',
        'IsCurrent' => true,
        'Name' => 'Example Holdings Pty Ltd',
        'NameType' => 'Entity Name',
        'Postcode' => '2600',
        'Score' => 100,
        'State' => 'ACT',
    ], ...$fields];
}

/**
 * Mount the Filament test form with the given fields, filled with the given state, e.g.
 * testForm(fn () => [AbnInput::make('abn')], ['abn' => '51824753556']). The fields are built
 * in a closure because Livewire builds the form again on every request.
 *
 * @param  Closure(): array<mixed>  $components
 * @param  array<string, mixed>  $state
 */
function testForm(Closure $components, array $state = []): Testable
{
    // Build the fields once here, so a field that can't be built fails with its own error
    // rather than one wrapped in Livewire's rendering of the form.
    $components();

    TestForm::using($components);

    return Livewire::test(TestForm::class, ['state' => $state]);
}

/**
 * The field with the given name on a mounted test form, e.g. formField($form, 'abn').
 */
function formField(Testable $form, string $name): Field
{
    $field = $form->instance()->getSchema('form')?->getFlatFields(withHidden: true)[$name] ?? null;

    if (! $field instanceof Field) {
        throw new RuntimeException("The test form has no field named [{$name}].");
    }

    return $field;
}

/**
 * Mount the Filament test table with the given columns, listing every record of the given
 * model, e.g. testTable(fn () => [AbnColumn::make('abn')], CastTestRecord::class). The columns
 * are built in a closure because Livewire builds the table again on every request.
 *
 * @param  Closure(): array<mixed>  $columns
 * @param  class-string<TestRecord>  $recordClass
 */
function testTable(Closure $columns, string $recordClass = TestRecord::class): Testable
{
    // Build the columns once here, so a column that can't be built fails with its own error
    // rather than one wrapped in Livewire's rendering of the table.
    $columns();

    TestTable::using($columns);

    return Livewire::test(TestTable::class, ['recordClass' => $recordClass]);
}

/**
 * Mount the Filament test infolist with the given entries, showing the given record, e.g.
 * testInfolist(fn () => [AbnEntry::make('abn')], $record).
 *
 * @param  Closure(): array<mixed>  $components
 */
function testInfolist(Closure $components, TestRecord $record): Testable
{
    // Build the entries once here, for the same reason as testTable().
    $components();

    TestInfolist::using($components);

    return Livewire::test(TestInfolist::class, ['record' => $record]);
}

/**
 * The text entry with the given name on a mounted test infolist, e.g.
 * infolistEntry($infolist, 'abn').
 */
function infolistEntry(Testable $infolist, string $name): TextEntry
{
    $entry = $infolist->instance()->getSchema('infolist')?->getFlatComponents(withHidden: true)[$name] ?? null;

    if (! $entry instanceof TextEntry) {
        throw new RuntimeException("The test infolist has no text entry named [{$name}].");
    }

    return $entry;
}

/**
 * The value an entry on a mounted test infolist shows for its record, as it's formatted, e.g.
 * "51 824 753 556". Tables have assertTableColumnFormattedStateSet() for the same check.
 */
function formattedEntryState(Testable $infolist, string $name): mixed
{
    $entry = infolistEntry($infolist, $name);

    return $entry->formatState($entry->getState());
}

/**
 * A log call as text: the message, then the context with any exception shown as its class and
 * message, as a log line would show it. Use it to check what a log entry gives away.
 *
 * @param  array<array-key, mixed>  $context
 */
function loggedText(string $message, array $context = []): string
{
    array_walk_recursive($context, function (mixed &$value): void {
        if ($value instanceof Throwable) {
            $value = $value::class.': '.$value->getMessage();
        }
    });

    return $message.' '.json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
}
