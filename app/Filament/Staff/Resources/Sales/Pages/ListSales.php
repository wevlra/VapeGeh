<?php

namespace App\Filament\Staff\Resources\Sales\Pages;

use App\Filament\Staff\Resources\Sales\SaleResource;
use Filament\Resources\Pages\ListRecords;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
