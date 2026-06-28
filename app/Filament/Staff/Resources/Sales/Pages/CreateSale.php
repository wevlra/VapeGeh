<?php

namespace App\Filament\Staff\Resources\Sales\Pages;

use App\Filament\Concerns\NotifiesWithDetail;
use App\Filament\Staff\Resources\Sales\SaleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = SaleResource::class;
}
