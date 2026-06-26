<?php

namespace App\Filament\Admin\Resources\Expenses\Pages;

use App\Filament\Admin\Resources\Expenses\ExpenseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExpense extends ViewRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
