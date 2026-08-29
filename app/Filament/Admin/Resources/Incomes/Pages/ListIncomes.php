<?php

namespace App\Filament\Admin\Resources\Incomes\Pages;

use App\Exports\IncomesExport;
use App\Filament\Admin\Resources\Incomes\IncomeResource;
use App\Filament\Concerns\HasExportActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListIncomes extends ListRecords
{
    use HasExportActions;
    use HasResponsiveTable;

    protected static string $resource = IncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getExportActionGroup(
                IncomesExport::class,
                'exports.pdf.incomes',
                'laporan-pendapatan'
            ),
            CreateAction::make(),
        ];
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['location.name', 'category', 'amount'])
            ->cardTitle(fn ($record) => $record->description ?? 'Tidak ada deskripsi');
    }
}
