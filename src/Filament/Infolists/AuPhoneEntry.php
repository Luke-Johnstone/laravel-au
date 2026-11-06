<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Infolists;

use EastpointLabs\LaravelAu\Filament\Concerns\CanBeDialled;
use EastpointLabs\LaravelAu\Filament\Display;
use EastpointLabs\LaravelAu\Filament\Label;
use Filament\Infolists\Components\TextEntry;

/**
 * Shows a stored Australian phone number formatted, such as "0491 570 156", in an infolist entry.
 */
class AuPhoneEntry extends TextEntry
{
    use CanBeDialled;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('phone'))
            ->formatStateUsing(static fn (mixed $state): mixed => Display::phone($state));
    }
}
