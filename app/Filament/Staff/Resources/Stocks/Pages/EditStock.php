<?php

namespace App\Filament\Staff\Resources\Stocks\Pages;

use App\Filament\Concerns\NotifiesWithDetail;
use App\Filament\Staff\Resources\Stocks\StockResource;
use Filament\Resources\Pages\EditRecord;

class EditStock extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
