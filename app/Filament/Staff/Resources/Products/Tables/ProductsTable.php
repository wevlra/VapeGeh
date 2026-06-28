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
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('stock_qty')
                    ->label('Stok')
                    ->getStateUsing(fn (Product $record): int => $record->stocks->firstWhere('location_id', $locationId)?->qty ?? 0)
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
