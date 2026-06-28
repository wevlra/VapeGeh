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
                                'other' => 'Lainnya',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'sale' => 'success',
                                'debt_payment' => 'info',
                                'other' => 'gray',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): ?string => match ($state) {
                                'sale' => 'heroicon-o-banknotes',
                                'debt_payment' => 'heroicon-o-receipt-refund',
                                'other' => 'heroicon-o-folder',
                                default => null,
                            }),
                        TextEntry::make('amount')
                            ->label('Jumlah')
                            ->money('IDR'),
                        TextEntry::make('description')
                            ->label('Deskripsi')
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
