<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Suppliers\Schemas;

use EastpointLabs\LaravelAu\Filament\Infolists\AbnEntry;
use EastpointLabs\LaravelAu\Filament\Infolists\AcnEntry;
use EastpointLabs\LaravelAu\Filament\Infolists\AuPhoneEntry;
use EastpointLabs\LaravelAu\Filament\Infolists\BsbEntry;
use EastpointLabs\LaravelAu\Filament\Infolists\StateEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Every infolist entry in the package.
 */
final class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Business')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Business name'),
                        AbnEntry::make('abn')
                            ->linkToAbnLookup(),
                        AcnEntry::make('acn')
                            ->placeholder('None'),
                        BsbEntry::make('bsb')
                            ->placeholder('None'),
                    ]),
                Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        AuPhoneEntry::make('phone')
                            ->dialable()
                            ->placeholder('None'),
                        AuPhoneEntry::make('mobile')
                            ->label('Mobile')
                            ->dialable()
                            ->placeholder('None'),
                    ]),
                Section::make('Address')
                    ->columns(2)
                    ->schema([
                        // The address fields' own labels, so the page matches the form.
                        TextEntry::make('street_line_1')
                            ->label(__('au::fields.street_line_1')),
                        TextEntry::make('street_line_2')
                            ->label(__('au::fields.street_line_2'))
                            ->placeholder('None'),
                        TextEntry::make('suburb')
                            ->label(__('au::fields.suburb')),
                        StateEntry::make('state'),
                        TextEntry::make('postcode')
                            ->label(__('au::fields.postcode')),
                    ]),
            ]);
    }
}
