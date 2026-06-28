<?php

namespace App\Filament\Admin\Resources\History\Pages;

use App\Filament\Admin\Resources\History\HistoryResource;
use App\Models\Sale;
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
            ->only(['type', 'location.name', 'quantity'])
            ->cardTitle(fn ($record) => $record->related instanceof Sale
                ? 'Sale ('.$record->related->invoice_number.')'
                : ($record->product?->name ?? '-'));
    }
}
