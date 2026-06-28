<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    use NotifiesWithDetail;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }
}
