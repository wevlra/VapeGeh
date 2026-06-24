<?php

namespace App\Filament\Staff\Resources\Stocks\Pages;

use App\Filament\Staff\Resources\Stocks\StockResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStock extends CreateRecord
{
    protected static string $resource = StockResource::class;
}
