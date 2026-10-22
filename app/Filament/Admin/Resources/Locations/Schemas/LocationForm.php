<?php

namespace App\Filament\Admin\Resources\Locations\Schemas;

use App\Models\Location;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Textarea::make('address')
                    ->label('Alamat'),
                TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(50),
                Select::make('type')
                    ->label('Tipe')
                    ->required()
                    ->options([
                        'store' => 'Toko',
                        'warehouse' => 'Gudang',
                    ])
                    ->default('store'),
                Select::make('price_type')
                    ->label('Tipe Harga')
                    ->required()
                    ->options(Location::PRICE_TYPE_OPTIONS)
                    ->default('1')
                    ->helperText('Produk yang belum memiliki harga pada tipe ini akan menggunakan Harga 1.'),
                Select::make('status')
                    ->label('Status')
                    ->required()
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                    ])
                    ->default('active'),
            ]);
    }
}
