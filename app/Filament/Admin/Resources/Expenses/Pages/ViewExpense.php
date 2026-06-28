<?php

namespace App\Filament\Admin\Resources\Expenses\Pages;

use App\Filament\Admin\Resources\Expenses\ExpenseResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewExpense extends ViewRecord
{
    use NotifiesWithDetail;

    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }
}
