<?php

namespace App\Filament\Admin\Resources\Expenses\Pages;

use App\Filament\Admin\Resources\Expenses\ExpenseResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = ExpenseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('view');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}
