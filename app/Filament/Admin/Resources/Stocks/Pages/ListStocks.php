<?php

namespace App\Filament\Admin\Resources\Stocks\Pages;

use App\Filament\Admin\Resources\Stocks\StockResource;
use App\Filament\Admin\Resources\Stocks\Widgets\WarehouseStockStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStocks extends ListRecords
{
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
}
