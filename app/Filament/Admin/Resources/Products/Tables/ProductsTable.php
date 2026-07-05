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
                    ->sortable()
                    ->view('filament.columns.admin-product-name'),
                TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 0, ',', '.'))
                    ->color('primary')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->stackedOnMobile()
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
