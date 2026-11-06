<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Concerns;

/**
 * Shared by StateColumn and StateEntry.
 *
 * @internal
 */
trait CanBeAbbreviated
{
    protected bool $isAbbreviated = false;

    /**
     * Show the state's code (NSW) rather than its name (New South Wales).
     */
    public function abbreviated(): static
    {
        $this->isAbbreviated = true;

        return $this;
    }

    /**
     * Whether abbreviated() was called.
     */
    public function isAbbreviated(): bool
    {
        return $this->isAbbreviated;
    }
}
