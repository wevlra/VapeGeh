<?php

namespace App\Filament\Admin\Resources\Incomes\Pages;

use App\Filament\Admin\Resources\Incomes\IncomeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIncome extends ViewRecord
{
    protected static string $resource = IncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
