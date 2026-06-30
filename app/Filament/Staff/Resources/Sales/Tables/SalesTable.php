<?php

namespace App\Filament\Staff\Resources\Sales\Tables;

use App\Actions\DeleteSale;
use App\Filament\Staff\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
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
                    ->openUrlInNewTab(),
                Action::make('print_invoice')
                    ->label('Cetak Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(fn (Sale $record): string => route('admin.history.invoice', $record->stockMovements()->first()))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make()
                    ->action(fn (Sale $record) => app(DeleteSale::class)->execute($record))
                    ->successNotification(
                        Notification::make()
                            ->title('Penjualan dihapus')
                            ->body('Penjualan telah dihapus secara permanen.')
                            ->danger()
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
