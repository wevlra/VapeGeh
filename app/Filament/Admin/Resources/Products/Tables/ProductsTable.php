<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Product;
use DomainException;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;

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
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        default => ucfirst($state),
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'inactive' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-minus',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('price_1')
                    ->label('Harga Jual')
                    ->formatStateUsing(function ($state, Product $record): string {
                        $prices = [];

                        if ($record->price_1 > 0) {
                            $prices[] = 'Rp '.number_format($record->price_1, 0, ',', '.');
                        }
                        if ($record->price_2 > 0) {
                            $prices[] = 'Rp '.number_format($record->price_2, 0, ',', '.');
                        }
                        if ($record->reseller_price > 0) {
                            $prices[] = 'Rp '.number_format($record->reseller_price, 0, ',', '.');
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
                    ->action(function (Product $record) {
                        try {
                            $record->delete();
                        } catch (QueryException $e) {
                            if (str_contains($e->getMessage(), 'FOREIGN KEY constraint failed')) {
                                NotifiesWithDetail::buildErrorNotification(
                                    'Produk Tidak Dapat Dihapus',
                                    'Produk "'.$record->name.'" masih memiliki data terkait (stok, penjualan, atau riwayat). Nonaktifkan atau hapus data terkait terlebih dahulu.'
                                )->send();

                                return;
                            }

                            throw $e;
                        } catch (DomainException $e) {
                            NotifiesWithDetail::buildErrorNotification(
                                'Produk Tidak Dapat Dihapus',
                                $e->getMessage()
                            )->send();

                            return;
                        }

                        NotifiesWithDetail::buildSuccessNotification(
                            'Data Berhasil Dihapus',
                            'Data produk telah berhasil dihapus dari sistem.'
                        )->send();
                    })
                    ->successNotification(null),
            ]);
    }
}
