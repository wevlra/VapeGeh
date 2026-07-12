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
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Pembayaran')
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
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                    ]),
            ])
            ->recordActions([
                Action::make('print_receipt')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Sale $record): string => route('admin.sales.receipt', $record))
                    ->openUrlInNewTab()
                    ->extraAttributes(function (Sale $record) {
                        return [
                            'x-on:click.prevent' => "
                                const url = '".route('admin.sales.receipt', ['sale' => $record->id, 'format' => 'json'])."';
                                
                                const savedPrinter = localStorage.getItem('savedPrinter');
                                if (savedPrinter) {
                                    window.dispatchEvent(new CustomEvent('print-native', { detail: { url } }));
                                } else {
                                    window.dispatchEvent(new CustomEvent('open-printer-modal', { detail: { url } }));
                                }
                            ",
                        ];
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
