<?php

namespace App\Filament\Admin\Resources\Incomes\Pages;

use App\Filament\Admin\Resources\Incomes\IncomeResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewIncome extends ViewRecord
{
    use NotifiesWithDetail;

    protected static string $resource = IncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }
}
