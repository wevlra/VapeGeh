<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = UserResource::class;

    /** @var array<string, mixed> */
    protected array $roleData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->roleData = [
            'role' => $data['role'] ?? 'staff',
            'status' => $data['status'] ?? 'active',
            'location_id' => $data['location_id'] ?? null,
        ];

        unset($data['role'], $data['status'], $data['location_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->role = $this->roleData['role'];
        $this->record->status = $this->roleData['status'];
        $this->record->location_id = $this->roleData['location_id'];
        $this->record->save();
    }
}
