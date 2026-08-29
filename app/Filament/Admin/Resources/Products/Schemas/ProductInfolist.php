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
                        TextEntry::make('category.name')
                            ->label('Kategori')
                            ->badge(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => 'Aktif',
                                'inactive' => 'Nonaktif',
                                default => ucfirst($state),
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'active' => 'heroicon-o-check-circle',
                                'inactive' => 'heroicon-o-x-circle',
                                default => 'heroicon-o-minus',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'danger',
                                default => 'gray',
                            }),
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
