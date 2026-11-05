<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Forms;

use EastpointLabs\LaravelAu\Filament\Label;
use EastpointLabs\LaravelAu\Rules\Bsb as BsbRule;
use EastpointLabs\LaravelAu\Support\Bsb;
use Filament\Forms\Components\TextInput;

/**
 * A BSB field, masked as "062-000" and saved as 6 digits.
 */
class BsbInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('bsb'))
            ->validationAttribute(Label::unchangedInMessages())
            ->mask('999-999')
            ->rule(new BsbRule)
            ->formatStateUsing(static fn (mixed $state): mixed => is_string($state) ? (Bsb::tryFrom($state)?->format() ?? $state) : $state)
            ->dehydrateStateUsing(static fn (mixed $state): mixed => is_string($state) ? (Bsb::tryFrom($state)->value ?? $state) : $state);
    }
}
