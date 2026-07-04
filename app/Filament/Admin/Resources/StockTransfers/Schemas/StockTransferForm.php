<?php

namespace App\Filament\Admin\Resources\StockTransfers\Schemas;

use App\Models\Product;
use App\Models\ProductPrice;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make()
                    ->columnSpanFull()
                    ->steps([
                        Step::make('Lokasi')
                            ->description('Pilih sumber dan tujuan')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('from_location_id')
                                            ->label('Dari Lokasi')
                                            ->relationship('fromLocation', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload(),
                                        Select::make('to_location_id')
                                            ->label('Ke Lokasi')
                                            ->relationship('toLocation', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->different('from_location_id'),
                                    ]),
                                Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(2),
                            ]),
                        Step::make('Daftar Item')
                            ->description('Pilih produk dan jumlah')
                            ->icon(Heroicon::OutlinedCube)
                            ->schema([
                                Repeater::make('items')
                                    ->relationship()
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->addActionLabel('Tambah Produk')
                                    ->table([
                                        TableColumn::make('Produk')
                                            ->width('45%'),
                                        TableColumn::make('Jumlah')
                                            ->width('25%'),
                                        TableColumn::make('Harga Jual Baru')
                                            ->width('30%'),
                                    ])
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Produk')
                                            ->relationship('product', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->reactive(),

                                        TextInput::make('qty')
                                            ->label('Jumlah')
                                            ->integer()
                                            ->minValue(1)
                                            ->maxValue(2147483647)
                                            ->default(1)
                                            ->required(),

                                        Select::make('price')
                                            ->label('Harga Jual Baru')
                                            ->options(function (callable $get): array {
                                                $productId = $get('product_id');
                                                if (! $productId) {
                                                    return [];
                                                }

                                                $product = Product::find($productId);
                                                if (! $product) {
                                                    return [];
                                                }

                                                $options = [];

                                                $defaultPrice = (float) $product->default_price;
                                                if ($defaultPrice > 0) {
                                                    $options['default'] = 'Default: Rp '.number_format($defaultPrice, 0, ',', '.');
                                                }

                                                foreach ($product->prices as $price) {
                                                    $options['pp_'.$price->id] = "{$price->label}: Rp ".number_format((float) $price->price, 0, ',', '.');
                                                }

                                                return $options;
                                            })
                                            ->formatStateUsing(function ($state, callable $get) {
                                                if (! $state) {
                                                    return null;
                                                }
                                                $productId = $get('product_id');
                                                if (! $productId) {
                                                    return $state;
                                                }
                                                $product = Product::find($productId);
                                                if (! $product) {
                                                    return $state;
                                                }

                                                if ((float) $state == (float) $product->default_price) {
                                                    return 'default';
                                                }

                                                foreach ($product->prices as $p) {
                                                    if ((float) $state == (float) $p->price) {
                                                        return 'pp_'.$p->id;
                                                    }
                                                }

                                                return $state;
                                            })
                                            ->dehydrateStateUsing(function ($state, callable $get) {
                                                if ($state === 'default') {
                                                    $product = Product::find($get('product_id'));

                                                    return $product ? (float) $product->default_price : null;
                                                }
                                                if ($state && str_starts_with($state, 'pp_')) {
                                                    $priceId = (int) str_replace('pp_', '', $state);
                                                    $priceModel = ProductPrice::find($priceId);

                                                    return $priceModel ? (float) $priceModel->price : null;
                                                }

                                                return $state;
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
