<?php

namespace App\Filament\Staff\Resources\Stocks\Tables;

use App\Filament\Staff\Resources\Stocks\StockResource;
use App\Models\Stock;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Stock $record): string => StockResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('qty')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('product.name');
    }
}
