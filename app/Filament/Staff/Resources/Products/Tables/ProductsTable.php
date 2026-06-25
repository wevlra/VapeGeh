<?php

namespace App\Filament\Staff\Resources\Products\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('store_price')
                    ->money('IDR'),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
