<?php

namespace App\Filament\Admin\Resources\Vendors\Pages;

use App\Filament\Admin\Resources\Vendors\VendorResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVendor extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }
}
