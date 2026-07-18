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
                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                            ->label('Harga Beli'),
                        TextEntry::make('price_1')
                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                            ->label('Harga 1'),
                        TextEntry::make('price_2')
                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                            ->label('Harga 2'),
                        TextEntry::make('reseller_price')
                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                            ->label('Harga Reseller'),
                    ]),
            ]);
    }
}
