<?php

namespace App\Filament\Admin\Resources\History\Tables;

use App\Filament\Actions\PrintInvoiceAction;
use App\Filament\Actions\PrintReceiptAction;
use App\Filament\Admin\Resources\History\HistoryResource;
use App\Models\Sale;
use App\Models\StockEntry;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

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
                        'opname' => 'heroicon-o-pencil',
                        'adjustment' => 'heroicon-o-pencil',
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

                        return $record->product?->name ?? '-';
                    }),
                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->sortable(),
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
                        } else {
                            $total = abs($record->quantity);
                        }

                        $prefix = $total > 0 && (! ($related instanceof StockOpname) || $total > 0) ? '+' : '';
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
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
                        'transfer' => 'Transfer',
                        'opname' => 'Penyesuaian Stok',
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
                Action::make('print_report')
                    ->label('Cetak Laporan')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->action(function () {})
                    ->mountUsing(function (StockMovement $record) {
                        $related = $record->related;
                        $timestamp = date('Y-m-d-H-i');

                        if ($related instanceof StockOpname) {
                            $related->load(['items.product', 'location', 'creator']);
                            $title = 'Laporan Penyesuaian Stok - '.$related->opname_number;
                            $view = 'exports.pdf.stock-opname';
                            $data = ['opname' => $related, 'title' => $title, 'filterInfo' => ''];
                            $fileName = "stock-opname-{$related->opname_number}-{$timestamp}.pdf";
                        } elseif ($related instanceof StockEntry) {
                            $related->load(['items.product', 'location', 'creator', 'vendor']);
                            $title = 'Laporan Stok Masuk';
                            $view = 'exports.pdf.stock-entry';
                            $data = ['entry' => $related, 'title' => $title, 'filterInfo' => ''];
                            $fileName = "stock-entry-{$related->id}-{$timestamp}.pdf";
                        } elseif ($related instanceof StockTransfer) {
                            $related->load(['items.product', 'fromLocation', 'toLocation', 'creator']);
                            $title = 'Laporan Transfer Stok - '.$related->transfer_number;
                            $view = 'exports.pdf.stock-transfer-detail';
                            $data = ['transfer' => $related, 'title' => $title, 'filterInfo' => ''];
                            $fileName = "stock-transfer-{$related->transfer_number}-{$timestamp}.pdf";
                        }

                        $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'portrait');

                        Storage::disk('public')->put("reports/{$fileName}", $pdf->output());

                        $url = asset("storage/reports/{$fileName}");

                        session()->put("report_url_{$record->id}", $url);
                        session()->put("report_filename_{$record->id}", $fileName);
                    })
                    ->modalHeading(false)
                    ->modalWidth('sm')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(fn (StockMovement $record) => view('exports.pdf.modal-preview', [
                        'url' => session()->get("report_url_{$record->id}"),
                        'filename' => session()->get("report_filename_{$record->id}"),
                        'title' => 'Cetak Laporan',
                    ]))
                    ->visible(fn (StockMovement $record): bool => in_array($record->type, ['in', 'transfer', 'opname'])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
