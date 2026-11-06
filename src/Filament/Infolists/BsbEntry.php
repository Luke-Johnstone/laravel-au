<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Infolists;

use EastpointLabs\LaravelAu\Filament\Display;
use EastpointLabs\LaravelAu\Filament\Label;
use Filament\Infolists\Components\TextEntry;

/**
 * Shows a stored BSB formatted, such as "062-000", in an infolist entry.
 */
class BsbEntry extends TextEntry
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('bsb'))
            ->formatStateUsing(static fn (mixed $state): mixed => Display::bsb($state));
    }
}
