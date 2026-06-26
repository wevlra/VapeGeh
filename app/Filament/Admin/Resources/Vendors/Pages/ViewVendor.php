<?php

namespace App\Filament\Admin\Resources\Vendors\Pages;

use App\Filament\Admin\Resources\Vendors\VendorResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVendor extends ViewRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
