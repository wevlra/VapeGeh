<?php

namespace App\Filament\Admin\Resources\History\Tables;

use App\Filament\Actions\PrintInvoiceAction;
use App\Filament\Actions\PrintReceiptAction;
use App\Filament\Admin\Resources\History\HistoryResource;
use App\Models\Sale;
use App\Models\StockMovement;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HistoryTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (StockMovement $record): string => HistoryResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Tanggal'),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
                        'transfer' => 'Transfer',
                        'adjustment' => 'Penyesuaian',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'transfer' => 'info',
                        'adjustment' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'in' => 'heroicon-o-arrow-down-tray',
                        'out' => 'heroicon-o-arrow-up-tray',
                        'transfer' => 'heroicon-o-arrow-right-end-on-rectangle',
                        'adjustment' => 'heroicon-o-pencil',
                        default => null,
                    }),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (StockMovement $record): string => $record->related instanceof Sale
                        ? 'Penjualan ('.$record->related->invoice_number.')'
                        : ($record->product?->name ?? '-')),
                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->getStateUsing(function (StockMovement $record): string {
                        $total = $record->related instanceof Sale
                            ? $record->related->items->sum('qty')
                            : abs($record->quantity);

                        return $record->quantity > 0 ? '+'.$total : (string) (-$total);
                    })
                    ->color(fn (StockMovement $record): string => $record->quantity > 0 ? 'success' : 'danger'),
                TextColumn::make('creator.name')
                    ->label('Staf')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
                        'transfer' => 'Transfer',
                        'adjustment' => 'Penyesuaian',
                    ]),
                SelectFilter::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name'),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                PrintReceiptAction::make('print_receipt'),
                PrintInvoiceAction::make('print_invoice'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
