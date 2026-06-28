<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->email()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8),
                Select::make('role')
                    ->label('Peran')
                    ->options([
                        'admin' => 'Admin',
                        'staff' => 'Staf',
                    ])
                    ->required(),
                Select::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Diperlukan untuk pengguna staf'),
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
