<?php

namespace App\Filament\Staff\Resources\Products\Tables;

use App\Filament\Staff\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
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
                Split::make([
                    Stack::make([
                        TextColumn::make('name')
                            ->weight('bold')
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('selling_price')
                            ->money('IDR')
                            ->color('gray')
                            ->sortable(),
                    ]),
                    Stack::make([
                        TextColumn::make('stock_qty')
                            ->badge()
                            ->alignEnd()
                            ->color(fn (int $state): string => match (true) {
                                $state <= 0 => 'danger',
                                $state < 10 => 'warning',
                                default => 'success',
                            })
                            ->getStateUsing(fn (Product $record): int => $record->stocks->firstWhere('location_id', $locationId)?->qty ?? 0),
                    ])->space(1),
                ])
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
