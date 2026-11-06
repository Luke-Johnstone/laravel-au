<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Infolists;

use EastpointLabs\LaravelAu\Filament\Concerns\CanBeAbbreviated;
use EastpointLabs\LaravelAu\Filament\Display;
use EastpointLabs\LaravelAu\Filament\Label;
use Filament\Infolists\Components\TextEntry;

/**
 * Shows a stored state by name, such as "New South Wales", or by code, in an infolist entry.
 */
class StateEntry extends TextEntry
{
    use CanBeAbbreviated;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('state'))
            ->formatStateUsing(static fn (self $component, mixed $state): mixed => Display::state($state, $component->isAbbreviated()));
    }
}
