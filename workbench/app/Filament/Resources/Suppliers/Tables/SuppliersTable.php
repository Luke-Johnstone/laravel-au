<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Suppliers\Tables;

use EastpointLabs\LaravelAu\Filament\Tables\AbnColumn;
use EastpointLabs\LaravelAu\Filament\Tables\AcnColumn;
use EastpointLabs\LaravelAu\Filament\Tables\AuPhoneColumn;
use EastpointLabs\LaravelAu\Filament\Tables\BsbColumn;
use EastpointLabs\LaravelAu\Filament\Tables\StateColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Every table column in the package.
 */
final class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Business name')
                    ->searchable()
                    ->sortable(),
                AbnColumn::make('abn')
                    ->linkToAbnLookup(),
                AcnColumn::make('acn'),
                BsbColumn::make('bsb'),
                AuPhoneColumn::make('phone')
                    ->dialable(),
                StateColumn::make('state')
                    ->abbreviated()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
