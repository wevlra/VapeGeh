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
                    ->required()
                    ->maxLength(255),
                Textarea::make('address'),
                Select::make('type')
                    ->required()
                    ->options([
                        'store' => 'Store',
                        'warehouse' => 'Warehouse',
                    ])
                    ->default('store')
                    ->visible(fn ($record) => ! $record),
                Select::make('status')
                    ->required()
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active'),
            ]);
    }
}
