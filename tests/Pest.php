<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Tests\TestCase;

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
