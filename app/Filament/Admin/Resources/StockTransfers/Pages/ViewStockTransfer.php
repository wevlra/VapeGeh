<?php

namespace App\Filament\Admin\Resources\StockTransfers\Pages;

use App\Actions\CompleteStockTransfer;
use App\Filament\Admin\Resources\StockTransfers\StockTransferResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewStockTransfer extends ViewRecord
{
    use NotifiesWithDetail;

    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('complete')
                ->label('Selesai')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Transfer Stok')
                ->modalDescription(fn ($record): string => "Yakin ingin menyelesaikan transfer {$record->transfer_number}?")
                ->hidden(fn ($record): bool => $record->status !== 'pending')
                ->action(function ($record): void {
                    try {
                        app(CompleteStockTransfer::class)->execute($record, auth()->user());

                        $this->notifySuccess(
                            'Transfer Selesai',
                            "Transfer \"{$record->transfer_number}\" telah selesai. Stok telah dipindahkan antar lokasi."
                        );

                        $this->refreshFormData(['status', 'completed_at']);
                    } catch (DomainException $e) {
                        $this->notifyError(
                            'Gagal Menyelesaikan Transfer Stok',
                            $e->getMessage()
                        );
                    }
                }),
            EditAction::make()
                ->hidden(fn ($record) => $record->status !== 'pending'),
            DeleteAction::make()
                ->hidden(fn ($record) => $record->status !== 'pending')
                ->successNotification(fn (): ?Notification => $this->getDeletedNotification()),
            Action::make('print_report')
                ->label('Cetak Laporan')
                ->icon('heroicon-o-printer')
                ->hidden(fn ($record) => $record->status !== 'completed')
                ->mountUsing(function ($record) {
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
                ->modalContent(fn ($record) => view('exports.pdf.modal-preview', [
                    'url' => session()->get("report_url_{$record->id}"),
                    'filename' => session()->get("report_filename_{$record->id}"),
                    'title' => 'Cetak Laporan',
                ])),
        ];
    }
}
