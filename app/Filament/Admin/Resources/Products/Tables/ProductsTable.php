<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
