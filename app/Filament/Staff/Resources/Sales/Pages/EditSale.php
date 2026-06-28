<?php

namespace App\Filament\Staff\Resources\Sales\Pages;

use App\Filament\Concerns\NotifiesWithDetail;
use App\Filament\Staff\Resources\Sales\SaleResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }
}
