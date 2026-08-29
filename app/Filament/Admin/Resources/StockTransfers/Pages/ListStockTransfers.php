<?php

namespace App\Filament\Admin\Resources\StockTransfers\Pages;

use App\Exports\StockTransfersExport;
use App\Filament\Admin\Resources\StockTransfers\StockTransferResource;
use App\Filament\Concerns\HasExportActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListStockTransfers extends ListRecords
{
    use HasExportActions;
    use HasResponsiveTable;

    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getExportActionGroup(
                StockTransfersExport::class,
                'exports.pdf.stock-transfers',
                'laporan-transfer-stok'
            ),
            CreateAction::make(),
        ];
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['transfer_number', 'status'])
            ->cardTitle(fn ($record) => $record->transfer_number);
    }
}
