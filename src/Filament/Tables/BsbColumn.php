<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Tables;

use EastpointLabs\LaravelAu\Filament\Display;
use EastpointLabs\LaravelAu\Filament\Label;
use Filament\Tables\Columns\TextColumn;

/**
 * Shows a stored BSB formatted, such as "062-000", in a table column.
 */
class BsbColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('bsb'))
            ->formatStateUsing(static fn (mixed $state): mixed => Display::bsb($state));
    }
}
