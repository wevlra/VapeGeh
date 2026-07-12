<?php

namespace App\Filament\Admin\Resources\Sales\Tables;

use App\Actions\DeleteSale;
use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
                TextColumn::make('location.name')
                    ->label('Lokasi')
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
                        'cash' => 'Tunai',
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
                SelectFilter::make('user_id')
                    ->label('Staf')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name'),
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('print_receipt')
                        ->label('Cetak Nota')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (Sale $record): string => route('admin.sales.receipt', $record))
                        ->openUrlInNewTab()
                        ->extraAttributes(function (Sale $record) {
                            return [
                                'x-on:click' => "
                                    if (window.Capacitor && window.Capacitor.isNativePlatform()) {
                                        \$event.preventDefault();
                                        const url = '".route('admin.sales.receipt', ['sale' => $record->id, 'format' => 'text'])."';
                                        
                                        const savedPrinter = localStorage.getItem('savedPrinter');
                                        if (savedPrinter) {
                                            window.dispatchEvent(new CustomEvent('print-native', { detail: { url } }));
                                        } else {
                                            window.dispatchEvent(new CustomEvent('open-printer-modal', { detail: { url } }));
                                        }
                                    }
                                ",
                            ];
                        }),
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
                            NotifiesWithDetail::buildErrorNotification(
                                'Data Berhasil Dihapus',
                                'Data transaksi penjualan telah berhasil dihapus dari sistem.'
                            )
                        ),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
