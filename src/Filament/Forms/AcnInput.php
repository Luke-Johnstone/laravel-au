<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Forms;

use EastpointLabs\LaravelAu\Filament\Label;
use EastpointLabs\LaravelAu\Rules\Acn as AcnRule;
use EastpointLabs\LaravelAu\Support\Acn;
use Filament\Forms\Components\TextInput;

/**
 * An ACN field, masked as "004 085 616" and saved as 9 digits.
 */
class AcnInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('acn'))
            ->validationAttribute(Label::unchangedInMessages())
            ->mask('999 999 999')
            ->rule(new AcnRule)
            ->formatStateUsing(static fn (mixed $state): mixed => is_string($state) ? (Acn::tryFrom($state)?->format() ?? $state) : $state)
            ->dehydrateStateUsing(static fn (mixed $state): mixed => is_string($state) ? (Acn::tryFrom($state)->value ?? $state) : $state);
    }
}
