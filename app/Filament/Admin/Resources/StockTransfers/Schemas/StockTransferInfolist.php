<?php

namespace App\Filament\Admin\Resources\StockTransfers\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockTransferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Transfer Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('transfer_number'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ->icon(fn (string $state): string => match ($state) {
                                'pending' => 'heroicon-o-clock',
                                'completed' => 'heroicon-o-check-circle',
                                'cancelled' => 'heroicon-o-x-circle',
                                default => 'heroicon-o-minus',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('fromLocation.name')
                            ->label('From'),
                        TextEntry::make('toLocation.name')
                            ->label('To'),
                        TextEntry::make('notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                        TextEntry::make('creator.name')
                            ->label('Created by'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),

                Section::make('Line Items')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Product'),
                                TableColumn::make('Quantity')
                                    ->width(100),
                            ])
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product'),
                                TextEntry::make('qty')
                                    ->label('Quantity'),
                            ]),
                    ]),
            ]);
    }
}
