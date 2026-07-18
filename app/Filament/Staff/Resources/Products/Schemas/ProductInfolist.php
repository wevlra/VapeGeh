<?php

namespace App\Filament\Staff\Resources\Products\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $locationId = auth()->user()?->location_id;

        return $schema
            ->schema([
                Section::make('Detail Produk')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama'),
                        TextEntry::make('category.name')
                            ->label('Kategori')
                            ->badge(),
                        TextEntry::make('price_1')
                            ->label('Harga 1')
                            ->getStateUsing(function ($record) use ($locationId): float {
                                $stock = $record->stocks->firstWhere('location_id', $locationId);

                                return $stock && $stock->price > 0 ? (float) $stock->price : (float) $record->price_1;
                            })
                            ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                            ->color('primary'),
                        TextEntry::make('stocks')
                            ->label('Stok')
                            ->getStateUsing(fn ($record): int => $record->stocks->firstWhere('location_id', $locationId)?->qty ?? 0)
                            ->badge()
                            ->color(fn ($record): string => match (true) {
                                ($record->stocks->firstWhere('location_id', $locationId)?->qty ?? 0) <= 0 => 'danger',
                                ($record->stocks->firstWhere('location_id', $locationId)?->qty ?? 0) < 10 => 'warning',
                                default => 'success',
                            }),
                    ]),
            ]);
    }
}
