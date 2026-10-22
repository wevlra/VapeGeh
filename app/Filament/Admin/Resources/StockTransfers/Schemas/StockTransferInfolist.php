<?php

namespace App\Filament\Admin\Resources\StockTransfers\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockTransferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Transfer')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('transfer_number')
                            ->label('No. Transfer'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Tertunda',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                                default => ucfirst($state),
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'pending' => 'heroicon-o-clock',
                                'completed' => 'heroicon-o-check-circle',
                                'cancelled' => 'heroicon-o-x-circle',
                                default => 'heroicon-o-minus',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('fromLocation.name')
                            ->label('Dari'),
                        TextEntry::make('toLocation.name')
                            ->label('Ke'),
                        TextEntry::make('creator.name')
                            ->label('Dibuat oleh'),
                        TextEntry::make('created_at')
                            ->label('Tanggal')
                            ->dateTime(),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ]),

                Section::make('Daftar Produk')
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
                    ]),
            ]);
    }
}
