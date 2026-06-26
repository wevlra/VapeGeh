<?php

namespace App\Filament\Staff\Resources\Stocks\Pages;

use App\Filament\Staff\Resources\Stocks\StockResource;
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
            //
        ];
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['product.name', 'qty'])
            ->cardTitle(fn ($record) => $record->product?->name ?? "Stock #{$record->id}");
    }
}
