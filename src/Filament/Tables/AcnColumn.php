<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Tables;

use EastpointLabs\LaravelAu\Filament\Display;
use EastpointLabs\LaravelAu\Filament\Label;
use Filament\Tables\Columns\TextColumn;

/**
 * Shows a stored ACN formatted, such as "004 085 616", in a table column.
 */
class AcnColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('acn'))
            ->formatStateUsing(static fn (mixed $state): mixed => Display::acn($state));
    }
}
