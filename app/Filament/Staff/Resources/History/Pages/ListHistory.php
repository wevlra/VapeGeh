<?php

namespace App\Filament\Staff\Resources\History\Pages;

use App\Filament\Staff\Resources\History\HistoryResource;
use App\Models\Sale;
use App\Models\StockMovement;
use Filament\Resources\Pages\ListRecords;
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
}
