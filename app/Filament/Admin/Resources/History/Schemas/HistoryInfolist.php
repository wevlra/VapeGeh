<?php

namespace App\Filament\Admin\Resources\History\Schemas;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockEntry;
use App\Models\StockOpname;
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
                                'opname' => 'Penyesuaian Stok',
                                'adjustment' => 'Penyesuaian',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'in' => 'success',
                                'out' => 'danger',
                                'transfer' => 'info',
                                'opname' => 'gray',
                                'adjustment' => 'gray',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): ?string => match ($state) {
                                'in' => 'heroicon-o-arrow-down-tray',
                                'out' => 'heroicon-o-arrow-up-tray',
                                'transfer' => 'heroicon-o-arrow-right-end-on-rectangle',
                                'opname' => 'heroicon-o-pencil-square',
                                'adjustment' => 'heroicon-o-pencil-square',
                                default => null,
                            }),
                        TextEntry::make('location.name')
                            ->label('Lokasi')
                            ->placeholder('Lokasi tidak ditentukan'),
                        TextEntry::make('creator.name')
                            ->label('Staf')
                            ->placeholder('Staf tidak diketahui'),
                        TextEntry::make('notes')
                            ->label('Catatan Pergerakan')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan'),
                    ]),

                Section::make('Detail Transaksi')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema(function ($record) {
                        if ($record->related instanceof Sale) {
                            return [
                                TextEntry::make('related.invoice_number')
                                    ->label('No. Invoice')
                                    ->placeholder('Tidak ada no. invoice'),
                                TextEntry::make('related.total')
                                    ->label('Total')
                                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                                TextEntry::make('related.payment_method')
                                    ->label('Pembayaran')
                                    ->placeholder('Metode pembayaran tidak ditentukan')
                                    ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                                        'qris' => 'QRIS',
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer',
                                        default => $state ? ucfirst($state) : null,
                                    }),
                                TextEntry::make('related.buyer.name')
                                    ->label('Pembeli')
                                    ->placeholder('Walk-in Customer'),
                                TextEntry::make('related.notes')
                                    ->label('Catatan')
                                    ->columnSpanFull()
                                    ->placeholder('Tidak ada catatan'),
                                RepeatableEntry::make('related.items')
                                    ->columnSpanFull()
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
                            ];
                        }

                        if ($record->related instanceof StockTransfer) {
                            return [
                                TextEntry::make('related.transfer_number')
                                    ->label('No. Transfer')
                                    ->placeholder('Tidak ada no. transfer'),
                                TextEntry::make('related.fromLocation.name')
                                    ->label('Dari')
                                    ->placeholder('Lokasi asal tidak ditentukan'),
                                TextEntry::make('related.toLocation.name')
                                    ->label('Ke')
                                    ->placeholder('Lokasi tujuan tidak ditentukan'),
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
                                TextEntry::make('related.notes')
                                    ->label('Catatan')
                                    ->columnSpanFull()
                                    ->placeholder('Tidak ada catatan'),
                                RepeatableEntry::make('related.items')
                                    ->columnSpanFull()
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Produk')
                                            ->width(350),
                                        TableColumn::make('Jumlah')
                                            ->width(80),
                                        TableColumn::make('Harga')->width(180),
                                        TableColumn::make('Subtotal')->width(180),
                                    ])
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label('Produk'),
                                        TextEntry::make('qty')
                                            ->label('Jumlah'),
                                        TextEntry::make('price')
                                            ->label('Harga')
                                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                                            ->state(function ($record) {
                                                if ($record->purchase_price !== null) {
                                                    return $record->purchase_price;
                                                }
                                                if ($record->relationLoaded('stockTransfer') || $record->stockTransfer()->exists()) {
                                                    if ($record->stockTransfer && $record->stockTransfer->toLocation) {
                                                        $toLocation = $record->stockTransfer->toLocation;
                                                        $targetPrice = $record->product ? $toLocation->priceFor($record->product) : 0;

                                                        return $targetPrice;
                                                    }
                                                }

                                                return $record->product->price_1 ?? 0;
                                            }),
                                        TextEntry::make('subtotal')
                                            ->label('Subtotal')
                                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                                            ->state(function ($record) {
                                                if ($record->purchase_price !== null) {
                                                    return $record->qty * $record->purchase_price;
                                                }
                                                $targetPrice = $record->product->price_1 ?? 0;
                                                if ($record->relationLoaded('stockTransfer') || $record->stockTransfer()->exists()) {
                                                    if ($record->stockTransfer && $record->stockTransfer->toLocation) {
                                                        $toLocation = $record->stockTransfer->toLocation;
                                                        $targetPrice = $record->product ? $toLocation->priceFor($record->product) : 0;
                                                    }
                                                }

                                                return $record->qty * $targetPrice;
                                            }),
                                    ]),
                            ];
                        }

                        if ($record->related instanceof StockEntry) {
                            $entry = $record->related;
                            $stockIn = $entry->type === 'in';
                            $total = $entry->total_amount;

                            return [
                                TextEntry::make('related.vendor.name')
                                    ->label('Vendor')
                                    ->hidden(fn () => ! $stockIn)
                                    ->placeholder('Vendor tidak ditentukan'),
                                TextEntry::make('related.buyer.name')
                                    ->label('Pembeli')
                                    ->hidden(fn () => $stockIn)
                                    ->placeholder('Pembeli tidak ditentukan'),
                                TextEntry::make('related.payment_method')
                                    ->label('Pembayaran')
                                    ->hidden(fn () => $stockIn)
                                    ->placeholder('Metode pembayaran tidak ditentukan')
                                    ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer',
                                        'qris' => 'QRIS',
                                        default => $state ? ucfirst($state) : null,
                                    }),
                                TextEntry::make('stock_entry_total')
                                    ->label('Total')
                                    ->state(fn () => $total)
                                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                                TextEntry::make('related.notes')
                                    ->label('Catatan')
                                    ->columnSpanFull()
                                    ->placeholder('Tidak ada catatan'),
                                RepeatableEntry::make('additional_costs')
                                    ->columnSpanFull()
                                    ->label('Biaya Tambahan')
                                    ->hidden(fn () => blank($entry->additional_costs))
                                    ->table([
                                        TableColumn::make('Deskripsi')->width(180),
                                        TableColumn::make('Jumlah')->width(80),
                                    ])
                                    ->schema([
                                        TextEntry::make('description')
                                            ->label('Deskripsi')
                                            ->placeholder('Tidak ada deskripsi'),
                                        TextEntry::make('amount')
                                            ->label('Jumlah')
                                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                                    ]),
                                RepeatableEntry::make('related.items')
                                    ->columnSpanFull()
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Produk')
                                            ->width(350),
                                        TableColumn::make('Jumlah')
                                            ->width(80),
                                        TableColumn::make('Harga Satuan')
                                            ->width(180),
                                    ])
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label('Produk'),
                                        TextEntry::make('qty')
                                            ->label('Jumlah'),
                                        TextEntry::make('unit_price')
                                            ->label('Harga Satuan')
                                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                                    ]),
                            ];
                        }

                        if ($record->related instanceof StockOpname) {
                            return [
                                TextEntry::make('related.opname_number')
                                    ->label('No. Opname')
                                    ->placeholder('Tidak ada no. opname'),
                                TextEntry::make('related.total_difference_value')
                                    ->label('Total Selisih Nilai')
                                    ->state(fn ($record) => $record?->related?->total_difference_value)
                                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                                    ->formatStateUsing(function ($state) {
                                        $prefix = $state > 0 ? '+' : '';

                                        return $prefix.'Rp '.number_format($state, 0, ',', '.');
                                    }),
                                TextEntry::make('related.notes')
                                    ->label('Catatan')
                                    ->columnSpanFull()
                                    ->placeholder('Tidak ada catatan'),
                                RepeatableEntry::make('related.items')
                                    ->columnSpanFull()
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Produk')
                                            ->width(350),
                                        TableColumn::make('Stok Sistem')
                                            ->width(80),
                                        TableColumn::make('Stok Fisik')
                                            ->width(80),
                                        TableColumn::make('Selisih')
                                            ->width(80),
                                        TableColumn::make('Selisih Nilai')
                                            ->width(180),
                                    ])
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label('Produk'),
                                        TextEntry::make('system_qty')
                                            ->label('Stok Sistem'),
                                        TextEntry::make('actual_qty')
                                            ->label('Stok Fisik'),
                                        TextEntry::make('difference')
                                            ->label('Selisih')
                                            ->formatStateUsing(fn ($state) => $state > 0 ? '+'.$state : $state),
                                        TextEntry::make('difference_value')
                                            ->label('Selisih Nilai')
                                            ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                                            ->formatStateUsing(function ($state) {
                                                $prefix = $state > 0 ? '+' : '';

                                                return $prefix.'Rp '.number_format($state, 0, ',', '.');
                                            }),
                                    ]),
                            ];
                        }

                        // Fallback for unknown/unsupported related types
                        // (e.g., Location::class from DeleteLocation action, or Stock::class from manual adjustment)
                        return [
                            RepeatableEntry::make('sibling_movements')
                                ->state(function ($record) {
                                    if ($record->related instanceof Stock) {
                                        return [$record];
                                    }

                                    return method_exists($record, 'siblingMovements') ? $record->siblingMovements : [$record];
                                })
                                ->columnSpanFull()
                                ->hiddenLabel()
                                ->table([
                                    TableColumn::make('Produk'),
                                    TableColumn::make('Jumlah'),
                                ])
                                ->schema([
                                    TextEntry::make('product.name')
                                        ->label('Produk'),
                                    TextEntry::make('quantity')
                                        ->label('Jumlah')
                                        ->state(fn ($record): string => (int) $record->quantity > 0 ? '+'.$record->quantity : (string) $record->quantity),
                                ]),
                        ];
                    }),
            ]);
    }
}
