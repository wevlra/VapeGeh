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
                    ->required()
                    ->maxLength(255),
                TextInput::make('purchase_price')
                    ->label('Purchase Price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp'),
                Repeater::make('prices')
                    ->relationship()
                    ->columnSpanFull()
                    ->label('Selling Prices')
                    ->defaultItems(1)
                    ->addActionLabel('Add Price')
                    ->table([
                        TableColumn::make('Label'),
                        TableColumn::make('Price'),
                    ])
                    ->schema([
                        TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('e.g. Reseller, Store, Wholesale'),
                        TextInput::make('price')
                            ->label('Price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp'),
                    ]),
            ]);
    }
}
