<?php

namespace App\Filament\Admin\Resources\Incomes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IncomeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Pendapatan')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('category')
                            ->label('Kategori')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'sale' => 'Penjualan',
                                'debt_payment' => 'Pembayaran Hutang',
                                'service' => 'Jasa / Service',
                                'cashback' => 'Cashback & Rebate',
                                'opname' => 'Penyesuaian Stok',
                                'investment' => 'Modal / Investasi',
                                'other' => 'Lainnya',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'sale' => 'success',
                                'debt_payment' => 'info',
                                'service' => 'primary',
                                'cashback' => 'warning',
                                'opname' => 'gray',
                                'investment' => 'success',
                                'other' => 'gray',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): ?string => match ($state) {
                                'sale' => 'heroicon-o-banknotes',
                                'debt_payment' => 'heroicon-o-receipt-refund',
                                'service' => 'heroicon-o-wrench-screwdriver',
                                'cashback' => 'heroicon-o-gift',
                                'opname' => 'heroicon-o-adjustments-horizontal',
                                'investment' => 'heroicon-o-currency-dollar',
                                'other' => 'heroicon-o-folder',
                                default => null,
                            }),
                        TextEntry::make('amount')
                            ->label('Jumlah')
                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.')),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->default('Tidak ada deskripsi')
                            ->color(fn ($state, $record) => blank($record->description) ? 'gray' : null)
                            ->columnSpanFull(),
                        TextEntry::make('location.name')
                            ->label('Lokasi'),
                        TextEntry::make('creator.name')
                            ->label('Dibuat oleh'),
                        TextEntry::make('date')
                            ->label('Tanggal')
                            ->date(),
                    ]),
            ]);
    }
}
