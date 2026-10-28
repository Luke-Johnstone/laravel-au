<?php

declare(strict_types=1);

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
