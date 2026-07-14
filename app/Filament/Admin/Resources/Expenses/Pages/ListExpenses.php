<?php

namespace App\Filament\Admin\Resources\Expenses\Pages;

use App\Exports\ExpensesExport;
use App\Filament\Admin\Resources\Expenses\ExpenseResource;
use App\Filament\Concerns\HasExportActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListExpenses extends ListRecords
{
    use HasExportActions;
    use HasResponsiveTable;

    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getExportActionGroup(
                ExpensesExport::class,
                'exports.pdf.expenses',
                'laporan-pengeluaran'
            ),
            CreateAction::make(),
        ];
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['location.name', 'category', 'amount'])
            ->cardTitle(fn ($record) => $record->description);
    }
}
