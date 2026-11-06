<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Tables;

use EastpointLabs\LaravelAu\Filament\Concerns\CanBeAbbreviated;
use EastpointLabs\LaravelAu\Filament\Display;
use EastpointLabs\LaravelAu\Filament\Label;
use Filament\Tables\Columns\TextColumn;

/**
 * Shows a stored state by name, such as "New South Wales", or by code, in a table column.
 */
class StateColumn extends TextColumn
{
    use CanBeAbbreviated;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('state'))
            ->formatStateUsing(static fn (self $component, mixed $state): mixed => Display::state($state, $component->isAbbreviated()));
    }
}
