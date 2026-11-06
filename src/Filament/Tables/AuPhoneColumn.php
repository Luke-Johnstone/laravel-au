<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Tables;

use EastpointLabs\LaravelAu\Filament\Concerns\CanBeDialled;
use EastpointLabs\LaravelAu\Filament\Display;
use EastpointLabs\LaravelAu\Filament\Label;
use Filament\Tables\Columns\TextColumn;

/**
 * Shows a stored Australian phone number formatted, such as "0491 570 156", in a table column.
 */
class AuPhoneColumn extends TextColumn
{
    use CanBeDialled;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('phone'))
            ->formatStateUsing(static fn (mixed $state): mixed => Display::phone($state));
    }
}
