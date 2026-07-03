<?php

namespace App\Filament\Staff\Resources\Products\Tables;

use App\Filament\Staff\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        $locationId = auth()->user()?->location_id;

        return $table
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->view('filament.columns.staff-product-name'),
                TextColumn::make('selling_price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('stok_saat_ini')
                    ->label('Stok')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    })
                    ->getStateUsing(fn (Product $record): int => (int) ($record->stocks->firstWhere('location_id', $locationId)?->qty ?? 0))
                    ->visibleFrom('md'),
            ])
            ->stackedOnMobile()
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
