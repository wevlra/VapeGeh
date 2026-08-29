<?php

namespace App\Filament\Admin\Resources\Vendors\Pages;

use App\Filament\Admin\Resources\Vendors\VendorResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewVendor extends ViewRecord
{
    use NotifiesWithDetail;

    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }
}
