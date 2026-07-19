<?php

namespace App\Filament\Staff\Resources\History\Tables;

use App\Filament\Actions\PrintReportAction;
use App\Filament\Staff\Resources\History\HistoryResource;
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
                        'opname' => 'Penyesuaian Stok',
                        'adjustment' => 'Penyesuaian',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'transfer' => 'info',
                        'opname' => 'gray',
                        'adjustment' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'in' => 'heroicon-o-arrow-down-tray',
                        'out' => 'heroicon-o-arrow-up-tray',
                        'transfer' => 'heroicon-o-arrow-right-end-on-rectangle',
                        'opname' => 'heroicon-o-pencil-square',
                        'adjustment' => 'heroicon-o-pencil-square',
                        default => null,
                    }),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->formatStateUsing(function (StockMovement $record): string {
                        $related = $record->related;

                        if ($related instanceof Sale) {
                            $items = $related->items->pluck('product.name')->filter()->toArray();
                            $count = count($items);
                            if ($count > 1) {
                                return $items[0].' + '.($count - 1).' lainnya';
                            }

                            return $items[0] ?? '-';
                        }

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

                        if ($related instanceof StockOpname) {
                            $items = $related->items->pluck('product.name')->filter()->toArray();
                            $count = count($items);
                            if ($count > 1) {
                                return $items[0].' + '.($count - 1).' lainnya';
                            }

                            return $items[0] ?? '-';
                        }

                        if ($related === null && $record->related_type === Location::class) {
                            $names = $record->sibling_movements->pluck('product.name')->filter()->toArray();
                            $count = count($names);
                            if ($count > 1) {
                                return $names[0].' + '.($count - 1).' lainnya';
                            }

                            return $names[0] ?? $record->product?->name ?? '-';
                        }

                        return $record->product?->name ?? '-';
                    }),
                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->getStateUsing(function (StockMovement $record): string {
                        $related = $record->related;
                        if ($related instanceof Sale) {
                            $total = $related->items->sum('qty');
                        } elseif ($related instanceof StockTransfer) {
                            $total = $related->items->sum('qty');
                        } elseif ($related instanceof StockEntry) {
                            $total = $related->items->sum('qty');
                        } elseif ($related instanceof StockOpname) {
                            $total = $related->items->sum('difference');
                        } elseif ($related === null && $record->related_type === Location::class) {
                            $total = $record->sibling_movements->sum('quantity');
                        } else {
                            $total = abs($record->quantity);
                        }

                        if ($related instanceof StockOpname) {
                            return $total > 0 ? '+'.$total : (string) $total;
                        }

                        return $record->quantity > 0 ? '+'.$total : (string) (-$total);
                    })
                    ->color(fn (StockMovement $record): string => $record->quantity > 0 ? 'success' : 'danger'),
                TextColumn::make('creator.name')
                    ->label('Staf')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->getStateUsing(function (StockMovement $record): ?string {
                        return $record->notes ?? $record->related?->notes;
                    })
                    ->searchable()
                    ->limit(50)
                    ->default('Tidak ada catatan')
                    ->color(fn ($state, $record) => blank($record->notes ?? $record->related?->notes) ? 'gray' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->recordActions([
                PrintReportAction::make('print_report'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
