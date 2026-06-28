<?php

namespace App\Filament\Admin\Resources\History\Schemas;

use App\Models\Sale;
use App\Models\StockTransfer;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HistoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Tanggal')
                            ->dateTime(),
                        TextEntry::make('type')
                            ->label('Tipe')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'in' => 'Stok Masuk',
                                'out' => 'Stok Keluar',
                                'transfer_in' => 'Transfer Masuk',
                                'transfer_out' => 'Transfer Keluar',
                                'adjustment' => 'Penyesuaian',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'in', 'transfer_in' => 'success',
                                'out', 'transfer_out' => 'danger',
                                'adjustment' => 'gray',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): ?string => match ($state) {
                                'in' => 'heroicon-o-arrow-down-tray',
                                'out' => 'heroicon-o-arrow-up-tray',
                                'transfer_in' => 'heroicon-o-arrow-right',
                                'transfer_out' => 'heroicon-o-arrow-left',
                                'adjustment' => 'heroicon-o-pencil',
                                default => null,
                            }),
                        TextEntry::make('location.name')
                            ->label('Lokasi'),
                        TextEntry::make('unit_price')
                            ->label('Harga Satuan')
                            ->money('IDR')
                            ->hidden(fn ($state): bool => is_null($state)),
                        TextEntry::make('creator.name')
                            ->label('Staf'),
                        TextEntry::make('buyer.name')
                            ->label('Pembeli')
                            ->hidden(fn ($state): bool => is_null($state)),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->hidden(fn ($state): bool => blank($state)),
                    ]),

                Section::make('Transaksi Terkait')
                    ->columnSpanFull()
                    ->schema(function ($record) {
                        if ($record->related instanceof Sale) {
                            return [
                                TextEntry::make('related.invoice_number')
                                    ->label('No. Invoice'),
                                TextEntry::make('related.total')
                                    ->label('Total')
                                    ->money('IDR'),
                                TextEntry::make('related.payment_method')
                                    ->label('Pembayaran')
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'qris' => 'QRIS',
                                        'cash' => 'Tunai',
                                        default => ucfirst($state),
                                    }),
                                RepeatableEntry::make('related.items')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Produk')
                                            ->width(180),
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
                            ];
                        }

                        if ($record->related instanceof StockTransfer) {
                            return [
                                TextEntry::make('related.transfer_number')
                                    ->label('No. Transfer'),
                                TextEntry::make('related.fromLocation.name')
                                    ->label('Dari'),
                                TextEntry::make('related.toLocation.name')
                                    ->label('Ke'),
                                TextEntry::make('related.status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => 'Tertunda',
                                        'completed' => 'Selesai',
                                        'cancelled' => 'Dibatalkan',
                                        default => ucfirst($state),
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                                RepeatableEntry::make('related.items')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Produk'),
                                        TableColumn::make('Jumlah')
                                            ->width(100),
                                    ])
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label('Produk'),
                                        TextEntry::make('qty')
                                            ->label('Jumlah'),
                                    ]),
                            ];
                        }

                        return [];
                    }),
            ]);
    }
}
