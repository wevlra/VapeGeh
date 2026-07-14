<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Exports\ProductsExport;
use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Concerns\HasExportActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class ListProducts extends ListRecords
{
    use HasExportActions;
    use HasResponsiveTable;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getExportActionGroup(
                ProductsExport::class,
                'exports.pdf.products',
                'laporan-produk'
            ),
            CreateAction::make(),
        ];
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['category.name', 'purchase_price', 'selling_price'])
            ->cardTitle(fn ($record) => $record->name);
    }
}
