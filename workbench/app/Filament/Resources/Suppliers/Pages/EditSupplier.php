<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Suppliers\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Workbench\App\Filament\Resources\Suppliers\SupplierResource;

final class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
