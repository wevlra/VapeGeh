<?php

namespace App\Filament\Staff\Resources\StockOpnames\Pages;

use App\Filament\Staff\Resources\StockOpnames\StockOpnameResource;
use Filament\Resources\Pages\ViewRecord;

class ViewStockOpname extends ViewRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
