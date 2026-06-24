<?php

namespace App\Filament\Admin\Resources\Stocks\Pages;

use App\Filament\Admin\Resources\Stocks\StockResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Resources\Pages\CreateRecord;

class CreateStock extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = StockResource::class;
}
