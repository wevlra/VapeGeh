<?php

namespace App\Filament\Admin\Resources\History\Schemas;

use App\Models\Sale;
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
                                'opname' => 'heroicon-o-pencil',
                                'adjustment' => 'heroicon-o-pencil',
                                default => null,
                            }),
                        TextEntry::make('location.name')
                            ->label('Lokasi'),
                        TextEntry::make('creator.name')
                            ->label('Staf'),
                        TextEntry::make('notes')
                            ->label('Catatan Pergerakan')
                            ->columnSpanFull()
                            ->default('-'),
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
                                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                                TextEntry::make('related.payment_method')
                                    ->label('Pembayaran')
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'qris' => 'QRIS',
                                        'cash' => 'Tunai',
                                        default => ucfirst($state),
                                    }),
                                TextEntry::make('related.notes')
                                    ->label('Catatan')
                                    ->columnSpanFull()
                                    ->default('-'),
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
                                TextEntry::make('related.notes')
                                    ->label('Catatan')
                                    ->columnSpanFull()
                                    ->default('-'),
                                RepeatableEntry::make('related.items')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Produk'),
                                        TableColumn::make('Jumlah')
                                            ->width(100),
                                        TableColumn::make('Harga')
                                            ->width(150),
                                        TableColumn::make('Subtotal')
                                            ->width(150),
                                    ])
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label('Produk'),
                                        TextEntry::make('qty')
                                            ->label('Jumlah'),
                                        TextEntry::make('price')
                                            ->label('Harga')
                                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                                            ->state(fn ($record) => empty($record->price) ? $record->product->selling_price : $record->price),
                                        TextEntry::make('subtotal')
                                            ->label('Subtotal')
                                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                                            ->state(fn ($record) => $record->qty * (empty($record->price) ? $record->product->selling_price : $record->price)),
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
                                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                                    ]),
                                TextEntry::make('related.notes')
                                    ->label('Catatan')
                                    ->columnSpanFull()
                                    ->default('-'),
                                TextEntry::make('stock_entry_total')
                                    ->label('Total Harga')
                                    ->state(fn () => $total)
                                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
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
                                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                                    ]),
                            ];
                        }

                        if ($record->related instanceof StockOpname) {
                            return [
                                TextEntry::make('related.opname_number')
                                    ->label('No. Opname'),
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
                                    ->default('-'),
                                RepeatableEntry::make('related.items')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Produk'),
                                        TableColumn::make('Stok Sistem')
                                            ->width(100),
                                        TableColumn::make('Stok Fisik')
                                            ->width(100),
                                        TableColumn::make('Selisih')
                                            ->width(100),
                                        TableColumn::make('Selisih Nilai')
                                            ->width(150),
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
                        // (e.g., Location::class from DeleteLocation action)
                        return [
                            RepeatableEntry::make('sibling_movements')
                                ->hiddenLabel()
                                ->table([
                                    TableColumn::make('Produk'),
                                    TableColumn::make('Jumlah'),
                                ])
                                ->schema([
                                    TextEntry::make('product.name')
                                        ->label('Produk'),
                                    TextEntry::make('quantity_display')
                                        ->label('Jumlah')
                                        ->state(fn ($record): string => (int) $record->quantity > 0 ? '+'.$record->quantity : (string) $record->quantity),
                                ]),
                        ];
                    }),
            ]);
    }
}
