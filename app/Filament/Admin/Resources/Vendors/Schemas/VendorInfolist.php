<?php

namespace App\Filament\Admin\Resources\Vendors\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Vendor')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama'),
                        TextEntry::make('contact_person')
                            ->label('Kontak Person')
                            ->placeholder('Tidak ada kontak person'),
                        TextEntry::make('phone')
                            ->label('Telepon')
                            ->placeholder('Tidak ada telepon'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->placeholder('Tidak ada email'),
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->placeholder('Tidak ada alamat')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
