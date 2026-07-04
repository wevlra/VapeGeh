<?php

namespace App\Filament\Staff\Resources\StockOpnames\Pages;

use App\Filament\Staff\Resources\StockOpnames\StockOpnameResource;
use Filament\Resources\Pages\ListRecords;

class ListStockOpnames extends ListRecords
{
    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
