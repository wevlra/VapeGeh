<?php

namespace App\Filament\Staff\Resources\Products\Pages;

use App\Filament\Concerns\NotifiesWithDetail;
use App\Filament\Staff\Resources\Products\ProductResource;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
