<?php

namespace App\Filament\Admin\Resources\Locations\Pages;

use App\Actions\DeleteLocation;
use App\Filament\Admin\Resources\Locations\LocationResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteLocation::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }
}
