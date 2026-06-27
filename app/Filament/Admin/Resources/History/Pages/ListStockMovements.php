<?php

namespace App\Filament\Admin\Resources\History\Pages;

use App\Filament\Admin\Resources\History\StockMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;
}
