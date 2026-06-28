<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('purchase_price')
                    ->label('Harga Beli')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp'),
                TextInput::make('selling_price')
                    ->label('Harga Jual (Default)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp'),
                Repeater::make('prices')
                    ->relationship()
                    ->columnSpanFull()
                    ->label('Harga Jual')
                    ->defaultItems(1)
                    ->addActionLabel('Tambah Harga')
                    ->table([
                        TableColumn::make('Label'),
                        TableColumn::make('Price'),
                    ])
                    ->schema([
                        TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Misal: Reseller, Toko, Grosir'),
                        TextInput::make('price')
                            ->label('Harga')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp'),
                    ]),
            ]);
    }
}
