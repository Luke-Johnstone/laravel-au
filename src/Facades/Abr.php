<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Facades;

use EastpointLabs\LaravelAu\Abr\Contracts\LooksUpAbns;
use EastpointLabs\LaravelAu\Abr\Data\AbnDetails;
use EastpointLabs\LaravelAu\Abr\Testing\AbrFake;
use Illuminate\Support\Facades\Facade;

/**
 * ABN Lookup, resolved from the container.
 *
 * @method static \EastpointLabs\LaravelAu\Abr\Data\AbnDetails|null abn(\EastpointLabs\LaravelAu\Support\Abn|string $abn)
 * @method static \EastpointLabs\LaravelAu\Abr\Data\AbnDetails|null acn(\EastpointLabs\LaravelAu\Support\Acn|string $acn)
 * @method static list<\EastpointLabs\LaravelAu\Abr\Data\NameMatch> searchByName(string $name, int $maxResults = 10)
 * @method static \EastpointLabs\LaravelAu\Abr\Testing\AbrFake notFound(\EastpointLabs\LaravelAu\Support\Abn|string ...$abns)
 * @method static \EastpointLabs\LaravelAu\Abr\Testing\AbrFake suppressed(\EastpointLabs\LaravelAu\Support\Abn|string $abn, string $status = 'Active', bool $gst = false)
 * @method static \EastpointLabs\LaravelAu\Abr\Testing\AbrFake withNameMatches(array<array-key, \EastpointLabs\LaravelAu\Abr\Data\NameMatch|array<string, mixed>> $matches)
 * @method static \EastpointLabs\LaravelAu\Abr\Testing\AbrFake failing()
 * @method static \EastpointLabs\LaravelAu\Abr\Testing\AbrFake preventStrayLookups()
 * @method static void assertLookedUp(\EastpointLabs\LaravelAu\Support\Abn|string $abn)
 * @method static void assertNotLookedUp(\EastpointLabs\LaravelAu\Support\Abn|string $abn)
 * @method static void assertNothingLookedUp()
 * @method static void assertLookupCount(int $count)
 *
 * @see LooksUpAbns
 * @see AbrFake
 */
final class Abr extends Facade
{
    /**
     * Replace ABN Lookup with a fake that answers from the given details, for the rest of the
     * test. Each value is the details for the ABN it's keyed by, or an array of ABN Lookup
     * fields merged over an active record for that ABN.
     *
     * @param  array<array-key, AbnDetails|array<string, mixed>>  $abns
     */
    public static function fake(array $abns = []): AbrFake
    {
        $fake = new AbrFake($abns);

        self::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return LooksUpAbns::class;
    }
}
