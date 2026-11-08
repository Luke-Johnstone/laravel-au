<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Suppliers\Schemas;

use EastpointLabs\LaravelAu\Filament\Forms\AbnInput;
use EastpointLabs\LaravelAu\Filament\Forms\AcnInput;
use EastpointLabs\LaravelAu\Filament\Forms\AuAddressFields;
use EastpointLabs\LaravelAu\Filament\Forms\AuPhoneInput;
use EastpointLabs\LaravelAu\Filament\Forms\BsbInput;
use EastpointLabs\LaravelAu\Rules\ActiveAbn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Every form field in the package. The address fields add the state select and the postcode,
 * which is checked against the state.
 */
final class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Business')
                    ->columns(2)
                    ->schema([
                        AbnInput::make('abn')
                            ->lookup('name')
                            // Only for a new supplier: the seeded ABNs are generated, so saving
                            // one must not send it to ABN Lookup.
                            ->rule(new ActiveAbn, fn (string $operation): bool => $operation === 'create')
                            ->required()
                            ->helperText('Try 51 824 753 556, then press Tab to look it up.'),
                        TextInput::make('name')
                            ->label('Business name')
                            ->required(),
                        AcnInput::make('acn'),
                        BsbInput::make('bsb'),
                    ]),
                Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        AuPhoneInput::make('phone')
                            ->allowServiceNumbers(),
                        AuPhoneInput::make('mobile')
                            ->label('Mobile')
                            ->mobileOnly(),
                    ]),
                Section::make('Address')
                    ->schema([
                        AuAddressFields::make(),
                    ]),
            ]);
    }
}
