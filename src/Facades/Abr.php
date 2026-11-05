<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Facades;

use EastpointLabs\LaravelAu\Abr\Contracts\LooksUpAbns;
use Illuminate\Support\Facades\Facade;

/**
 * ABN Lookup, resolved from the container.
 *
 * @method static \EastpointLabs\LaravelAu\Abr\Data\AbnDetails|null abn(\EastpointLabs\LaravelAu\Support\Abn|string $abn)
 * @method static \EastpointLabs\LaravelAu\Abr\Data\AbnDetails|null acn(\EastpointLabs\LaravelAu\Support\Acn|string $acn)
 * @method static list<\EastpointLabs\LaravelAu\Abr\Data\NameMatch> searchByName(string $name, int $maxResults = 10)
 *
 * @see LooksUpAbns
 */
final class Abr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LooksUpAbns::class;
    }
}
