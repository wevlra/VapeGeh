<?php

namespace App\Filament\Admin\Resources\History\Pages;

use App\Exports\HistoryExport;
use App\Filament\Admin\Resources\History\HistoryResource;
use App\Filament\Concerns\HasExportActions;
use App\Models\Sale;
use App\Models\StockMovement;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListHistory extends ListRecords
{
    use HasExportActions;
    use HasResponsiveTable;

    protected static string $resource = HistoryResource::class;

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['type', 'location.name', 'quantity'])
            ->cardTitle(fn (StockMovement $record) => $record->related instanceof Sale
                ? 'Penjualan ('.$record->related->invoice_number.')'
                : $record->formatted_product_name);
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()
            ->whereIn('id', function ($sub) {
                $sub->selectRaw('MIN(id)')
                    ->from('stock_movements')
                    ->groupBy(['related_type', 'related_id', 'location_id']);
            })
            ->where(function ($query) {
                $query->where('type', '!=', 'transfer')
                    ->orWhere(function ($q) {
                        $q->where('type', 'transfer')
                            ->where('quantity', '>', 0);
                    });
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getExportActionGroup(
                HistoryExport::class,
                'exports.pdf.history',
                'laporan-riwayat-stok'
            ),
        ];
    }
}
