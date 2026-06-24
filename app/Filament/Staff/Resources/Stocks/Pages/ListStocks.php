<?php

namespace App\Filament\Staff\Resources\Stocks\Pages;

use App\Filament\Staff\Resources\Stocks\StockResource;
use Filament\Resources\Pages\ListRecords;

class ListStocks extends ListRecords
{
    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
