<?php

namespace App\Filament\Admin\Resources\Locations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LocationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Lokasi')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama'),
                        TextEntry::make('type')
                            ->label('Tipe')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'store' => 'Toko',
                                'warehouse' => 'Gudang',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'store' => 'primary',
                                'warehouse' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->placeholder('Tidak ada alamat')
                            ->columnSpanFull(),
                        TextEntry::make('phone')
                            ->label('Telepon')
                            ->placeholder('Tidak ada telepon'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => 'Aktif',
                                'inactive' => 'Nonaktif',
                                default => ucfirst($state),
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'active' => 'heroicon-o-check-circle',
                                'inactive' => 'heroicon-o-x-circle',
                                default => 'heroicon-o-minus',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('users_count')
                            ->counts('users')
                            ->label('Staf'),
                        TextEntry::make('total_asset')
                            ->label('Total Aset')
                            ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                            ->color('primary'),
                    ]),
            ]);
    }
}
