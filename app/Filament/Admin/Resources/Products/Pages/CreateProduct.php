<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = ProductResource::class;
}
