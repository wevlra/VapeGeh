<?php

namespace App\Filament\Admin\Resources\Locations\Schemas;

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
                    ->default('store')
                    ->visible(fn ($record) => ! $record),
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
