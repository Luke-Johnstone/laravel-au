<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Filament\Forms;

use EastpointLabs\LaravelAu\Filament\Label;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * A two-column Australian address: street, suburb, state, and a postcode checked against the
 * state.
 */
final class AuAddressFields
{
    private function __construct() {}

    /**
     * The fields are named with the prefix, so "billing_" gives billing_street_line_1,
     * billing_street_line_2, billing_suburb, billing_state and billing_postcode.
     */
    public static function make(string $prefix = ''): Grid
    {
        return Grid::make(2)->schema([
            TextInput::make("{$prefix}street_line_1")
                ->label(Label::for('street_line_1'))
                ->required()
                ->columnSpanFull(),
            TextInput::make("{$prefix}street_line_2")
                ->label(Label::for('street_line_2'))
                ->columnSpanFull(),
            TextInput::make("{$prefix}suburb")
                ->label(Label::for('suburb'))
                ->required()
                ->columnSpanFull(),
            StateSelect::make("{$prefix}state")
                ->required(),
            AuPostcodeInput::make("{$prefix}postcode")
                ->forState("{$prefix}state")
                ->required(),
        ]);
    }
}
