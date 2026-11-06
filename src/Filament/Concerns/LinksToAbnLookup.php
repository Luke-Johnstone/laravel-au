<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Concerns;

use EastpointLabs\LaravelAu\Filament\Display;

/**
 * Shared by AbnColumn and AbnEntry.
 *
 * @internal
 */
trait LinksToAbnLookup
{
    /**
     * Link the ABN to its page on ABN Lookup, in a new tab.
     */
    public function linkToAbnLookup(): static
    {
        return $this->url(static fn (mixed $state): ?string => Display::abnLookupUrl($state), shouldOpenInNewTab: true);
    }
}
