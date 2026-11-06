<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Concerns;

use EastpointLabs\LaravelAu\Filament\Display;

/**
 * Shared by AuPhoneColumn and AuPhoneEntry.
 *
 * @internal
 */
trait CanBeDialled
{
    /**
     * Link the number with tel:, so a click dials it.
     */
    public function dialable(): static
    {
        return $this->url(static fn (mixed $state): ?string => Display::telUrl($state));
    }
}
