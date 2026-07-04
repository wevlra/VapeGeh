<?php

namespace App\Filament\Admin\Resources\StockOpnames\Schemas;

use App\Models\StockOpname;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockOpnameInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Utama')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('opname_number')
                            ->label('No. Opname'),
                        TextEntry::make('location.name')
                            ->label('Lokasi'),
                        TextEntry::make('total_difference_value')
                            ->label('Total Selisih Nilai')
                            ->state(fn (StockOpname $record): float => $record->total_difference_value)
                            ->color(fn (float $state): string => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                            ->formatStateUsing(function (float $state): string {
                                $prefix = $state > 0 ? '+' : '';

                                return $prefix.'Rp '.number_format($state, 0, ',', '.');
                            }),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'draft' => 'Draft',
                                'completed' => 'Selesai',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'warning',
                                'completed' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('creator.name')
                            ->label('Dibuat Oleh'),
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime(),
                        TextEntry::make('completed_at')
                            ->label('Selesai Pada')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                Section::make('Daftar Produk')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
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
                                TableColumn::make('Catatan'),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Produk'),
                                TextEntry::make('system_qty')
                                    ->label('Stok Sistem'),
                                TextEntry::make('actual_qty')
                                    ->label('Stok Fisik'),
                                TextEntry::make('difference')
                                    ->label('Selisih'),
                                TextEntry::make('difference_value')
                                    ->label('Selisih Nilai')
                                    ->color(fn (float $state): string => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                                    ->formatStateUsing(function (float $state): string {
                                        $prefix = $state > 0 ? '+' : '';

                                        return $prefix.'Rp '.number_format($state, 0, ',', '.');
                                    }),
                                TextEntry::make('notes')
                                    ->label('Catatan'),
                            ]),
                    ]),
            ]);
    }
}
