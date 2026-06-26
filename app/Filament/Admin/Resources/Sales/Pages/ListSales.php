<?php

namespace App\Filament\Admin\Resources\Sales\Pages;

use App\Filament\Admin\Resources\Sales\SaleResource;
use Filament\Resources\Pages\ListRecords;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListSales extends ListRecords
{
    use HasResponsiveTable;

    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['location.name', 'total'])
            ->cardTitle(fn ($record) => $record->invoice_number);
    }
}
