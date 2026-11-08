<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Suppliers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use Workbench\App\Filament\Resources\Suppliers\Pages\EditSupplier;
use Workbench\App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use Workbench\App\Filament\Resources\Suppliers\Pages\ViewSupplier;
use Workbench\App\Filament\Resources\Suppliers\Schemas\SupplierForm;
use Workbench\App\Filament\Resources\Suppliers\Schemas\SupplierInfolist;
use Workbench\App\Filament\Resources\Suppliers\Tables\SuppliersTable;
use Workbench\App\Models\Supplier;

/**
 * Suppliers, shown with every field, column and entry the package provides.
 */
final class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupplierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'view' => ViewSupplier::route('/{record}'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}
