<?php

namespace App\Filament\Staff\Resources\Products\Pages;

use App\Filament\Concerns\NotifiesWithDetail;
use App\Filament\Staff\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = ProductResource::class;
}
