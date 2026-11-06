<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Infolists;

use EastpointLabs\LaravelAu\Filament\Display;
use EastpointLabs\LaravelAu\Filament\Label;
use Filament\Infolists\Components\TextEntry;

/**
 * Shows a stored ACN formatted, such as "004 085 616", in an infolist entry.
 */
class AcnEntry extends TextEntry
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('acn'))
            ->formatStateUsing(static fn (mixed $state): mixed => Display::acn($state));
    }
}
