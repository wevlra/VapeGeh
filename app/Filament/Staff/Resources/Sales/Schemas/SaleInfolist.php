<?php

namespace App\Filament\Staff\Resources\Sales\Schemas;

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
                            ->money('IDR'),
                        TextEntry::make('user.name')
                            ->label('Kasir'),
                        TextEntry::make('payment_method')
                            ->label('Pembayaran')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'qris' => 'QRIS',
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
                        TextEntry::make('paid_amount')
                            ->label('Jumlah Dibayar')
                            ->money('IDR'),
                        TextEntry::make('notes')
                            ->label('Catatan'),
                    ]),

                Section::make('Item')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Produk'),
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
                                    ->money('IDR'),
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('IDR'),
                            ]),
                    ]),
            ]);
    }
}
