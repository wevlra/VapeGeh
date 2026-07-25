<?php

namespace App\Filament\Admin\Resources\StockTransfers\Tables;

use App\Actions\CompleteStockTransfer;
use App\Filament\Admin\Resources\StockTransfers\StockTransferResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\StockTransfer;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class StockTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (StockTransfer $record): string => StockTransferResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('transfer_number')
                    ->label('No. Transfer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fromLocation.name')
                    ->label('Dari'),
                TextColumn::make('toLocation.name')
                    ->label('Ke'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Tertunda',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'completed' => 'heroicon-o-check-circle',
                        'cancelled' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-minus',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Dibuat oleh'),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Tertunda',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (StockTransfer $record): bool => $record->status !== 'pending'),
                Action::make('complete')
                    ->label('Selesai')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Transfer Stok')
                    ->modalDescription(fn (StockTransfer $record): string => "Yakin ingin menyelesaikan transfer {$record->transfer_number}? Stok akan dikurangi dari sumber dan ditambahkan ke tujuan.")
                    ->hidden(fn (StockTransfer $record): bool => $record->status !== 'pending')
                    ->action(function (StockTransfer $record): void {
                        try {
                            app(CompleteStockTransfer::class)->execute(
                                $record,
                                auth()->user(),
                            );

                            NotifiesWithDetail::buildSuccessNotification(
                                'Transfer Selesai',
                                "Transfer \"{$record->transfer_number}\" telah selesai. Stok telah dipindahkan antar lokasi."
                            )->send();
                        } catch (DomainException $e) {
                            NotifiesWithDetail::buildErrorNotification(
                                'Transfer Gagal',
                                $e->getMessage()
                            )->send();
                        }
                    }),
                Action::make('print_report')
                    ->label('Cetak Laporan')
                    ->icon('heroicon-o-printer')
                    ->hidden(fn (StockTransfer $record): bool => $record->status !== 'completed')
                    ->mountUsing(function (StockTransfer $record) {
                        $record->load(['items.product', 'fromLocation', 'toLocation', 'creator']);
                        $timestamp = date('Y-m-d-H-i');
                        $title = 'Laporan Transfer Stok - '.$record->transfer_number;
                        $view = 'exports.pdf.stock-transfer-detail';
                        $data = ['transfer' => $record, 'title' => $title, 'filterInfo' => ''];
                        $fileName = "stock-transfer-{$record->transfer_number}-{$timestamp}.pdf";

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
                    ->modalContent(fn (StockTransfer $record) => view('exports.pdf.modal-preview', [
                        'url' => session()->get("report_url_{$record->id}"),
                        'filename' => session()->get("report_filename_{$record->id}"),
                        'title' => 'Cetak Laporan',
                    ])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
