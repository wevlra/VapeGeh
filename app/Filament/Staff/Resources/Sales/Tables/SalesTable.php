<?php

namespace App\Filament\Staff\Resources\Sales\Tables;

use App\Filament\Staff\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Actions\Action;
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
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                    ]),
            ])
            ->recordActions([
                Action::make('print_receipt')
                    ->label('Print Receipt')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Sale $record): string => route('admin.sales.receipt', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
