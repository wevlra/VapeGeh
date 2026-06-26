<?php

namespace App\Filament\Admin\Resources\Sales\Tables;

use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Sale $record): string => SaleResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Cashier')
                    ->searchable(),
                TextColumn::make('location.name')
                    ->searchable(),
                TextColumn::make('total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_method')
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
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Staff')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name'),
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                    ]),
            ])
            ->recordActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}
