<?php

namespace App\Filament\Admin\Resources\History\Schemas;

use App\Models\Sale;
use App\Models\StockEntry;
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
                                'transfer' => 'Transfer',
                                'adjustment' => 'Penyesuaian',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'in' => 'success',
                                'out' => 'danger',
                                'transfer' => 'info',
                                'adjustment' => 'gray',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): ?string => match ($state) {
                                'in' => 'heroicon-o-arrow-down-tray',
                                'out' => 'heroicon-o-arrow-up-tray',
                                'transfer' => 'heroicon-o-arrow-right-end-on-rectangle',
                                'adjustment' => 'heroicon-o-pencil',
                                default => null,
                            }),
                        TextEntry::make('location.name')
                            ->label('Lokasi'),
                        TextEntry::make('creator.name')
                            ->label('Staf'),
                    ]),

                Section::make('Detail Transaksi')
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

                        if ($record->related instanceof StockEntry) {
                            $entry = $record->related;
                            $stockIn = $entry->type === 'in';
                            $total = $entry->items->sum(fn ($i) => $i->qty * (float) $i->unit_price);

                            return [
                                TextEntry::make('related.vendor.name')
                                    ->label('Vendor')
                                    ->hidden(fn () => ! $stockIn)
                                    ->default('-'),
                                TextEntry::make('related.buyer.name')
                                    ->label('Pembeli')
                                    ->hidden(fn () => $stockIn)
                                    ->default('-'),
                                TextEntry::make('related.payment_method')
                                    ->label('Pembayaran')
                                    ->hidden(fn () => $stockIn || blank($entry->payment_method))
                                    ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer',
                                        'qris' => 'QRIS',
                                        default => $state,
                                    }),
                                RepeatableEntry::make('additional_costs')
                                    ->label('Biaya Tambahan')
                                    ->hidden(fn () => blank($entry->additional_costs))
                                    ->table([
                                        TableColumn::make('Deskripsi')->width(180),
                                        TableColumn::make('Jumlah')->width(80),
                                    ])
                                    ->schema([
                                        TextEntry::make('description')
                                            ->label('Deskripsi'),
                                        TextEntry::make('amount')
                                            ->label('Jumlah')
                                            ->money('IDR'),
                                    ]),
                                TextEntry::make('related.notes')
                                    ->label('Catatan')
                                    ->columnSpanFull()
                                    ->hidden(fn ($state): bool => blank($state)),
                                TextEntry::make('stock_entry_total')
                                    ->label('Total Harga')
                                    ->state(fn () => $total)
                                    ->money('IDR'),
                                RepeatableEntry::make('related.items')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Produk')->width(180),
                                        TableColumn::make('Jumlah')->width(80),
                                        TableColumn::make('Harga Satuan')->width(180),
                                    ])
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label('Produk'),
                                        TextEntry::make('qty')
                                            ->label('Jumlah'),
                                        TextEntry::make('unit_price')
                                            ->label('Harga Satuan')
                                            ->money('IDR'),
                                    ]),
                            ];
                        }

                        return [];
                    }),
            ]);
    }
}
