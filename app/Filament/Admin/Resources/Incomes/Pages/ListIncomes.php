<?php

namespace App\Filament\Admin\Resources\Incomes\Pages;

use App\Filament\Admin\Resources\Incomes\IncomeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListIncomes extends ListRecords
{
    use HasResponsiveTable;

    protected static string $resource = IncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['description', 'amount'])
            ->cardTitle(fn ($record) => $record->description);
    }
}
