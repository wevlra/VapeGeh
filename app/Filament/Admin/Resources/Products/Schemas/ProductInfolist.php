<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
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
                        TextEntry::make('sku')
                            ->label('SKU'),
                        TextEntry::make('name')
                            ->label('Nama'),
                        TextEntry::make('purchase_price')
                            ->money('IDR')
                            ->label('Harga Beli'),
                        TextEntry::make('selling_price')
                            ->money('IDR')
                            ->label('Harga Jual (Default)'),
                    ]),

                Section::make('Harga Jual')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('prices')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Label'),
                                TableColumn::make('Harga'),
                            ])
                            ->schema([
                                TextEntry::make('label')
                                    ->label('Label'),
                                TextEntry::make('price')
                                    ->label('Harga')
                                    ->money('IDR'),
                            ]),
                    ]),
            ]);
    }
}
