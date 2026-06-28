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
                Section::make('Product Details')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('sku'),
                        TextEntry::make('name'),
                        TextEntry::make('stocks')
                            ->label('Stock')
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
