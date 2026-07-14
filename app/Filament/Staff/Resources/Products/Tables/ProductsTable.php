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
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->getStateUsing(function (Product $record) use ($locationId): float {
                        $stock = $record->stocks->firstWhere('location_id', $locationId);

                        return $stock && $stock->price > 0 ? (float) $stock->price : (float) $record->selling_price;
                    })
                    ->money('IDR')
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('current_stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    })
                    ->getStateUsing(fn (Product $record): int => (int) ($record->stocks->firstWhere('location_id', $locationId)?->qty ?? 0)),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
