<?php

namespace App\Filament\Admin\Pages;

use App\Models\Stock;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Wezlo\FilamentResponsiveTable\Concerns\HasResponsiveTable;
use Wezlo\FilamentResponsiveTable\ResponsiveTableConfiguration;

class StockReport extends Page implements Tables\Contracts\HasTable
{
    use HasResponsiveTable;
    use Tables\Concerns\InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $title = 'Laporan Stok';

    protected string $view = 'filament.admin.pages.stock-report';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament-responsive-table::responsive-table'),
            ]);
    }

    public function responsiveTable(ResponsiveTableConfiguration $config): ResponsiveTableConfiguration
    {
        return $config
            ->only(['product.name', 'location.name', 'qty'])
            ->cardTitle(fn ($record) => $record->product->sku);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Stock::query()->with(['product', 'location']))
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('qty')
                    ->label('Jumlah')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_id')
                    ->relationship('location', 'name')
                    ->label('Lokasi')
                    ->preload(),
            ])
            ->defaultSort('location_id');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StockReportStats::class,
        ];
    }
}
