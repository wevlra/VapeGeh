<?php

namespace App\Filament\Staff\Resources\Products\Pages;

use App\Filament\Staff\Resources\Products\ProductResource;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
