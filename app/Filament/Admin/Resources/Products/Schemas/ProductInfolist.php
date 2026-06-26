<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

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
                        TextEntry::make('vendor.name')
                            ->label('Vendor'),
                    ]),

                Section::make('Pricing')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('purchase_price')
                            ->money('IDR')
                            ->label('Purchase Price'),
                        TextEntry::make('reseller_price')
                            ->money('IDR')
                            ->label('Reseller Price'),
                        TextEntry::make('store_price')
                            ->money('IDR')
                            ->label('Store Price'),
                    ]),
            ]);
    }
}
