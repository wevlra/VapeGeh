<?php

namespace App\Filament\Admin\Resources\Locations\Pages;

use App\Filament\Admin\Resources\Locations\LocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListLocations extends ListRecords
{
    use HasResponsiveTable;

    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['name', 'total_asset', 'status'])
            ->cardTitle(fn ($record) => $record->name);
    }
}
