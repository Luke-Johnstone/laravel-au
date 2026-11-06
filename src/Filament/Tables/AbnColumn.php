<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Tables;

use EastpointLabs\LaravelAu\Filament\Concerns\LinksToAbnLookup;
use EastpointLabs\LaravelAu\Filament\Display;
use EastpointLabs\LaravelAu\Filament\Label;
use Filament\Tables\Columns\TextColumn;

/**
 * Shows a stored ABN formatted, such as "51 824 753 556", in a table column.
 */
class AbnColumn extends TextColumn
{
    use LinksToAbnLookup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('abn'))
            ->formatStateUsing(static fn (mixed $state): mixed => Display::abn($state));
    }
}
