<?php

namespace App\Filament\Admin\Resources\Stocks\Pages;

use App\Filament\Admin\Resources\Stocks\StockResource;
use App\Filament\Admin\Resources\Stocks\Widgets\WarehouseStockStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListStocks extends ListRecords
{
    use HasResponsiveTable;

    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            WarehouseStockStats::class,
        ];
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['product.name', 'qty'])
            ->cardTitle(fn ($record) => $record->product?->name ?? "Stock #{$record->id}");
    }
}
