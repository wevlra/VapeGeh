<?php

namespace App\Filament\Staff\Resources\Products\Pages;

use App\Filament\Staff\Resources\Products\ProductResource;
use Filament\Resources\Pages\ListRecords;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListProducts extends ListRecords
{
    use HasResponsiveTable;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['category.name', 'price_1', 'current_stock'])
            ->cardTitle(fn ($record) => $record->name);
    }
}
