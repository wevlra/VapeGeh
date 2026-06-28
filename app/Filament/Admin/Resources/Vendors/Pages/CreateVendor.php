<?php

namespace App\Filament\Admin\Resources\Vendors\Pages;

use App\Filament\Admin\Resources\Vendors\VendorResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = VendorResource::class;
}
