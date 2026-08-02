<?php

namespace App\Filament\Staff\Resources\History\Pages;

use App\Filament\Staff\Resources\History\HistoryResource;
use App\Models\Sale;
use App\Models\StockMovement;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListHistory extends ListRecords
{
    use HasResponsiveTable;

    protected static string $resource = HistoryResource::class;

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['type', 'quantity'])
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
            ->whereIn('type', ['in', 'transfer'])
            ->where(function ($query) {
                $query->where('type', '!=', 'transfer')
                    ->orWhere(function ($q) {
                        $q->where('type', 'transfer')
                            ->where('quantity', '>', 0);
                    });
            });
    }
}
