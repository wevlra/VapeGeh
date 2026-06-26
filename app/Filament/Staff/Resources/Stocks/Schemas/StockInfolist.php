<?php

namespace App\Filament\Staff\Resources\Stocks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Stock Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('product.name')
                            ->label('Product'),
                        TextEntry::make('product.sku')
                            ->label('SKU'),
                        TextEntry::make('location.name')
                            ->label('Location'),
                        TextEntry::make('qty')
                            ->label('Quantity')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state <= 0 => 'danger',
                                $state < 10 => 'warning',
                                default => 'success',
                            }),
                    ]),
            ]);
    }
}
