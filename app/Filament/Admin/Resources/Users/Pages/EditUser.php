<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['role'], $data['status'], $data['location_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->role = request()->input('data.role', $this->record->role);
        $this->record->status = request()->input('data.status', $this->record->status);
        $this->record->location_id = request()->input('data.location_id', $this->record->location_id);
        $this->record->save();
    }
}
