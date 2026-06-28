<?php

namespace App\Filament\Staff\Resources\Sales\Pages;

use App\Filament\Staff\Resources\Sales\SaleResource;
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
            ->only(['invoice_number', 'total'])
            ->cardTitle(fn ($record) => $record->invoice_number);
    }
}
