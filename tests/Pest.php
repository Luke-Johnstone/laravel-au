<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Abr\JsonpDecoder;
use EastpointLabs\LaravelAu\Tests\TestCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

pest()->extend(TestCase::class)->in(__DIR__);

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
