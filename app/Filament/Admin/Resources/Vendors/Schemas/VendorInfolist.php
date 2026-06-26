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
                Section::make('Vendor Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('contact_person')
                            ->label('Contact Person')
                            ->placeholder('—'),
                        TextEntry::make('phone')
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->placeholder('—'),
                        TextEntry::make('address')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('products_count')
                            ->counts('products')
                            ->label('Products'),
                    ]),
            ]);
    }
}
