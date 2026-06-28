<?php

namespace App\Filament\Admin\Resources\History\Schemas;

use App\Models\Sale;
use App\Models\StockTransfer;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HistoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime(),
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'in' => 'Stock In',
                                'out' => 'Stock Out',
                                'transfer_in' => 'Transfer In',
                                'transfer_out' => 'Transfer Out',
                                'adjustment' => 'Adjustment',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'in', 'transfer_in' => 'success',
                                'out', 'transfer_out' => 'danger',
                                'adjustment' => 'gray',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): ?string => match ($state) {
                                'in' => 'heroicon-o-arrow-down-tray',
                                'out' => 'heroicon-o-arrow-up-tray',
                                'transfer_in' => 'heroicon-o-arrow-right',
                                'transfer_out' => 'heroicon-o-arrow-left',
                                'adjustment' => 'heroicon-o-pencil',
                                default => null,
                            }),
                        TextEntry::make('location.name')
                            ->label('Location'),
                        TextEntry::make('unit_price')
                            ->label('Unit Price')
                            ->money('IDR')
                            ->hidden(fn ($state): bool => is_null($state)),
                        TextEntry::make('creator.name')
                            ->label('Staff'),
                        TextEntry::make('buyer.name')
                            ->label('Buyer')
                            ->hidden(fn ($state): bool => is_null($state)),
                        TextEntry::make('notes')
                            ->columnSpanFull()
                            ->hidden(fn ($state): bool => blank($state)),
                    ]),

                Section::make('Related Transaction')
                    ->columnSpanFull()
                    ->schema(function ($record) {
                        if ($record->related instanceof Sale) {
                            return [
                                TextEntry::make('related.invoice_number')
                                    ->label('Invoice #'),
                                TextEntry::make('related.total')
                                    ->label('Total')
                                    ->money('IDR'),
                                TextEntry::make('related.payment_method')
                                    ->label('Payment')
                                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                                RepeatableEntry::make('related.items')
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
                                        TextEntry::make('product.name'),
                                        TextEntry::make('qty'),
                                        TextEntry::make('price')->money('IDR'),
                                        TextEntry::make('subtotal')->money('IDR'),
                                    ]),
                            ];
                        }

                        if ($record->related instanceof StockTransfer) {
                            return [
                                TextEntry::make('related.transfer_number')
                                    ->label('Transfer #'),
                                TextEntry::make('related.fromLocation.name')
                                    ->label('From'),
                                TextEntry::make('related.toLocation.name')
                                    ->label('To'),
                                TextEntry::make('related.status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                                RepeatableEntry::make('related.items')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Product'),
                                        TableColumn::make('Quantity')
                                            ->width(100),
                                    ])
                                    ->schema([
                                        TextEntry::make('product.name'),
                                        TextEntry::make('qty'),
                                    ]),
                            ];
                        }

                        return [];
                    }),
            ]);
    }
}
