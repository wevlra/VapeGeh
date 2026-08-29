<?php

namespace App\Filament\Admin\Resources\StockOpnames\Pages;

use App\Filament\Admin\Resources\StockOpnames\StockOpnameResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListStockOpnames extends ListRecords
{
    use HasResponsiveTable;

    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['location.name', 'total_difference_value', 'status'])
            ->cardTitle(fn ($record) => $record->opname_number);
    }
}
