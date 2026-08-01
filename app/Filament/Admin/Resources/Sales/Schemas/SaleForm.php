<?php

namespace App\Filament\Admin\Resources\Sales\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('location_id'),
                Repeater::make('items')
                    ->label('Item')
                    ->schema([
                        Select::make('product_id')
                            ->label('Produk')
                            ->options(function ($state, callable $get) {
                                $locationId = $get('../../location_id');
                                if (! $locationId) {
                                    return Product::whereHas('stocks', fn ($q) => $q->where('qty', '>', 0))
                                        ->pluck('name', 'id');
                                }

                                return Product::whereHas('stocks', fn ($q) => $q
                                    ->where('location_id', $locationId)
                                    ->where('qty', '>', 0),
                                )->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('price', (string) (float) $product->price_1);
                                    }
                                } else {
                                    $set('price', null);
                                }
                            }),
                        TextInput::make('qty')
                            ->label('Jumlah')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Select::make('price')
                            ->label('Harga')
                            ->options(function (callable $get) {
                                $productId = $get('product_id');
                                if (! $productId) {
                                    return [];
                                }
                                $product = Product::find($productId);
                                if (! $product) {
                                    return [];
                                }

                                $options = [];
                                $options[(string) (float) $product->price_1] = 'Harga 1 (Rp '.number_format($product->price_1, 0, ',', '.').')';
                                $options[(string) (float) $product->price_2] = 'Harga 2 (Rp '.number_format($product->price_2, 0, ',', '.').')';
                                $options[(string) (float) $product->reseller_price] = 'Harga Reseller (Rp '.number_format($product->reseller_price, 0, ',', '.').')';

                                $currentPrice = $get('price');
                                if ($currentPrice && ! isset($options[(string) (float) $currentPrice])) {
                                    $options[(string) (float) $currentPrice] = 'Harga Custom (Rp '.number_format((float) $currentPrice, 0, ',', '.').')';
                                }

                                return $options;
                            })
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->addActionLabel('Tambah Item')
                    ->deleteAction(fn ($action) => $action->label('Hapus')),
                Select::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                    ])
                    ->default('cash')
                    ->required(),
                TextInput::make('paid_amount')
                    ->label('Jumlah Dibayar')
                    ->numeric()
                    ->prefix('Rp')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->stripCharacters('.'),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull()
                    ->placeholder('Catatan untuk transaksi ini...')
                    ->maxLength(1000),
            ]);
    }
}
