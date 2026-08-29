<?php

namespace App\Filament\Admin\Resources\Locations\Pages;

use App\Filament\Admin\Resources\Locations\LocationResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = LocationResource::class;
}
