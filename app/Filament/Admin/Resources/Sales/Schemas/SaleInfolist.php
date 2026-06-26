<?php

namespace App\Filament\Admin\Resources\Sales\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Sale Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('invoice_number'),
                        TextEntry::make('total')
                            ->money('IDR'),
                        TextEntry::make('user.name')
                            ->label('Cashier'),
                        TextEntry::make('location.name')
                            ->label('Location'),
                        TextEntry::make('payment_method')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'qris' => 'QRIS',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'cash' => 'success',
                                'transfer' => 'info',
                                'qris' => 'primary',
                                'other' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),

                Section::make('Items')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Product'),
                                TableColumn::make('Qty')
                                    ->width(80),
                                TableColumn::make('Price')
                                    ->width(180),
                                TableColumn::make('Subtotal')
                                    ->width(180),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product'),
                                TextEntry::make('qty')
                                    ->label('Qty'),
                                TextEntry::make('price')
                                    ->label('Price')
                                    ->money('IDR'),
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('IDR'),
                            ]),
                    ]),
            ]);
    }
}
