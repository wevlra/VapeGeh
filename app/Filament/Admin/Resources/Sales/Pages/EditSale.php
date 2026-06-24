<?php

namespace App\Filament\Admin\Resources\Sales\Pages;

use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
