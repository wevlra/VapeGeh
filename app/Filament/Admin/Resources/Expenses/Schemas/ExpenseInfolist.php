<?php

namespace App\Filament\Admin\Resources\Expenses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Pengeluaran')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('category')
                            ->label('Kategori')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'purchase' => 'Pembelian',
                                'salary' => 'Gaji',
                                'utilities' => 'Utilitas',
                                'transport' => 'Transportasi',
                                'other' => 'Lainnya',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'purchase' => 'warning',
                                'salary' => 'primary',
                                'utilities' => 'info',
                                'transport' => 'gray',
                                'other' => 'gray',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): ?string => match ($state) {
                                'purchase' => 'heroicon-o-shopping-cart',
                                'salary' => 'heroicon-o-user-group',
                                'utilities' => 'heroicon-o-bolt',
                                'transport' => 'heroicon-o-truck',
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
