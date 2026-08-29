<?php

namespace App\Filament\Admin\Resources\Vendors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('contact_person')
                    ->label('Kontak Person')
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('address')
                    ->label('Alamat')
                    ->maxLength(255),
            ]);
    }
}
