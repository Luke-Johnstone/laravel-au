<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Forms;

use EastpointLabs\LaravelAu\Filament\Label;
use EastpointLabs\LaravelAu\Rules\AuPostcode;
use EastpointLabs\LaravelAu\Support\State;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

/**
 * An Australian postcode field, optionally checked against the state chosen in another field.
 */
class AuPostcodeInput extends TextInput
{
    protected ?string $stateField = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(Label::for('postcode'))
            ->maxLength(4)
            // The numeric keypad, without type="number", which would drop the 0 from 0800.
            ->inputMode('numeric')
            ->rule(static fn (self $component, Get $get): AuPostcode => new AuPostcode($component->stateFrom($get)));
    }

    /**
     * Check the postcode belongs to the state chosen in another field. While that field is
     * empty, or holds something that isn't a state, any state's postcode passes.
     */
    public function forState(string $stateField): static
    {
        $this->stateField = $stateField;

        return $this;
    }

    private function stateFrom(Get $get): ?State
    {
        if ($this->stateField === null) {
            return null;
        }

        $state = $get($this->stateField);

        return $state instanceof State ? $state : State::fromLoose(is_string($state) ? $state : null);
    }
}
