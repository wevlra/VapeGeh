<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Product;
use Filament\Actions\DeleteAction;
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
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->formatStateUsing(function (string|int|float $state, Product $record): string {
                        $prices = [];

                        if ($record->selling_price > 0) {
                            $prices[] = 'Rp '.number_format($record->selling_price, 0, ',', '.');
                        }
                        if ($record->reseller_price > 0) {
                            $prices[] = 'Rp '.number_format($record->reseller_price, 0, ',', '.');
                        }
                        if ($record->price_1 > 0) {
                            $prices[] = 'Rp '.number_format($record->price_1, 0, ',', '.');
                        }
                        if ($record->price_2 > 0) {
                            $prices[] = 'Rp '.number_format($record->price_2, 0, ',', '.');
                        }

                        return empty($prices) ? '-' : implode(', ', $prices);
                    })
                    ->color('primary')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->successNotification(
                        NotifiesWithDetail::buildErrorNotification(
                            'Data Berhasil Dihapus',
                            'Data produk telah berhasil dihapus dari sistem.'
                        )
                    ),
            ]);
    }
}
