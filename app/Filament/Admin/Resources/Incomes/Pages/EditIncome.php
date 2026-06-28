<?php

namespace App\Filament\Admin\Resources\Incomes\Pages;

use App\Filament\Admin\Resources\Incomes\IncomeResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditIncome extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = IncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }
}
