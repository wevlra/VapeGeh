<?php

namespace App\Filament\Staff\Resources\History\Tables;

use App\Filament\Staff\Resources\History\HistoryResource;
use App\Models\StockEntry;
use App\Models\StockMovement;
use App\Models\StockTransfer;
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
                        'transfer' => 'Transfer',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'transfer' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'in' => 'heroicon-o-arrow-down-tray',
                        'transfer' => 'heroicon-o-arrow-right-end-on-rectangle',
                        default => null,
                    }),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->formatStateUsing(function (StockMovement $record): string {
                        $related = $record->related;

                        if ($related instanceof StockTransfer) {
                            $items = $related->items->pluck('product.name')->filter()->toArray();
                            $count = count($items);
                            if ($count > 1) {
                                return $items[0].' + '.($count - 1).' lainnya';
                            }

                            return $items[0] ?? '-';
                        }

                        if ($related instanceof StockEntry) {
                            $items = $related->items->pluck('product.name')->filter()->toArray();
                            $count = count($items);
                            if ($count > 1) {
                                return $items[0].' + '.($count - 1).' lainnya';
                            }

                            return $items[0] ?? '-';
                        }

                        return $record->product?->name ?? '-';
                    }),
                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->getStateUsing(function (StockMovement $record): string {
                        $related = $record->related;
                        if ($related instanceof StockTransfer) {
                            $total = $related->items->sum('qty');
                        } elseif ($related instanceof StockEntry) {
                            $total = $related->items->sum('qty');
                        } else {
                            $total = abs($record->quantity);
                        }

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
                        'transfer' => 'Transfer',
                    ]),
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
            ->defaultSort('created_at', 'desc');
    }
}
