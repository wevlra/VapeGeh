<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Produk')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama'),
                        TextEntry::make('purchase_price')
                            ->money('IDR')
                            ->label('Harga Beli'),
                        TextEntry::make('selling_price')
                            ->money('IDR')
                            ->label('Harga Jual (Default)'),
                        TextEntry::make('reseller_price')
                            ->money('IDR')
                            ->label('Reseller Price'),
                        TextEntry::make('price_1')
                            ->money('IDR')
                            ->label('Harga 1'),
                        TextEntry::make('price_2')
                            ->money('IDR')
                            ->label('Harga 2'),
                    ]),
            ]);
    }
}
