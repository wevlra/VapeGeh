<?php

namespace App\Filament\Admin\Resources\Sales\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Penjualan')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('invoice_number')
                            ->label('No. Invoice'),
                        TextEntry::make('total')
                            ->label('Total')
                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                        TextEntry::make('user.name')
                            ->label('Kasir'),
                        TextEntry::make('buyer.name')
                            ->label('Pembeli')
                            ->default('Walk-in Customer'),
                        TextEntry::make('location.name')
                            ->label('Lokasi'),
                        TextEntry::make('payment_method')
                            ->label('Pembayaran')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'qris' => 'QRIS',
                                'cash' => 'Tunai',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'cash' => 'success',
                                'transfer' => 'info',
                                'qris' => 'primary',
                                'other' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->label('Tanggal')
                            ->dateTime(),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan'),
                    ]),

                Section::make('Item')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Produk')
                                    ->width(350),
                                TableColumn::make('Jumlah')
                                    ->width(80),
                                TableColumn::make('Harga')
                                    ->width(180),
                                TableColumn::make('Subtotal')
                                    ->width(180),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Produk'),
                                TextEntry::make('qty')
                                    ->label('Jumlah'),
                                TextEntry::make('price')
                                    ->label('Harga')
                                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                            ]),
                    ]),
            ]);
    }
}
