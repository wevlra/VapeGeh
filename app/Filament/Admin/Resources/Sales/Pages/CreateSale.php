<?php

namespace App\Filament\Admin\Resources\Sales\Pages;

use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = SaleResource::class;
}
