<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

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
                Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(255)
                            ->unique('categories', 'name'),
                        TextInput::make('description')
                            ->label('Deskripsi (opsional)')
                            ->maxLength(255),
                    ]),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                    ])
                    ->default('active')
                    ->required(),
                TextInput::make('purchase_price')
                    ->label('Harga Beli')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->stripCharacters('.'),
                TextInput::make('price_1')
                    ->label('Harga 1')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->stripCharacters('.'),
                TextInput::make('price_2')
                    ->label('Harga 2')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->prefix('Rp')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->stripCharacters('.'),
                TextInput::make('reseller_price')
                    ->label('Harga Reseller')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->prefix('Rp')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->stripCharacters('.'),
            ]);
    }
}
