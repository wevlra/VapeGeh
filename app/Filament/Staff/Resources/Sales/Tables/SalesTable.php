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
                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
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
                    ->placeholder('Tidak ada catatan')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                                window.dispatchEvent(new CustomEvent('open-receipt-action-modal', {
                                    detail: {
                                        jsonUrl: '".route('admin.sales.receipt', ['sale' => $record->id, 'format' => 'json'])."',
                                        receiptUrl: '".route('admin.sales.receipt', $record)."',
                                    }
                                }));
                            ",
                        ];
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
