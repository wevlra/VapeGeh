<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Product Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('sku'),
                        TextEntry::make('name'),
                        TextEntry::make('purchase_price')
                            ->money('IDR')
                            ->label('Purchase Price'),
                    ]),

                Section::make('Selling Prices')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('prices')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Label'),
                                TableColumn::make('Price'),
                            ])
                            ->schema([
                                TextEntry::make('label')
                                    ->label('Label'),
                                TextEntry::make('price')
                                    ->label('Price')
                                    ->money('IDR'),
                            ]),
                    ]),
            ]);
    }
}
