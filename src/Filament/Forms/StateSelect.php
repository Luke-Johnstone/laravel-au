<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Forms;

use EastpointLabs\LaravelAu\Filament\Label;
use EastpointLabs\LaravelAu\Support\State;
use Filament\Forms\Components\Select;
use Illuminate\Validation\Rule;

/**
 * A select of the eight Australian states and territories, saved as their codes (NSW, VIC...).
 */
class StateSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('state'))
            ->options(State::options())
            // The enum rule checks a single value; Filament checks each value of a multiple select.
            ->rule(Rule::enum(State::class), static fn (Select $component): bool => ! $component->isMultiple());
    }
}
