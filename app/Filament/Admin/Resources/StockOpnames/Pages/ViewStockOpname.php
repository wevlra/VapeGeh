<?php

namespace App\Filament\Admin\Resources\StockOpnames\Pages;

use App\Actions\CompleteStockOpname;
use App\Filament\Admin\Resources\StockOpnames\StockOpnameResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewStockOpname extends ViewRecord
{
    use NotifiesWithDetail;

    protected static string $resource = StockOpnameResource::class;

    public ?string $generatedPdfUrl = null;

    public ?string $generatedPdfFilename = null;

    public ?string $generatedPdfTitle = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('complete')
                ->label('Selesai')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Stock Opname')
                ->modalDescription(fn ($record): string => "Yakin ingin menyelesaikan opname {$record->opname_number}? Stok di lokasi akan disesuaikan berdasarkan stok fisik.")
                ->hidden(fn ($record) => $record->status !== 'draft')
                ->action(function ($record): void {
                    try {
                        app(CompleteStockOpname::class)->execute($record);

                        $this->notifySuccess(
                            'Stock Opname Selesai',
                            "Opname \"{$record->opname_number}\" telah selesai."
                        );

                        $this->refreshFormData(['status', 'completed_at']);
                    } catch (\DomainException $e) {
                        $this->notifyError(
                            'Gagal Menyelesaikan Stock Opname',
                            $e->getMessage()
                        );
                    }
                }),
            Action::make('print')
                ->label('Cetak Laporan')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->hidden(fn ($record) => $record->status !== 'completed')
                ->action(function () {
                    // Modal handle
                })
                ->mountUsing(function () {
                    $this->record->load(['items.product', 'location', 'creator']);

                    $title = 'Laporan Penyesuaian Stok - '.$this->record->opname_number;
                    $pdf = Pdf::loadView('exports.pdf.stock-opname', [
                        'opname' => $this->record,
                        'title' => $title,
                        'filterInfo' => '',
                    ])->setPaper('a4', 'portrait');

                    $timestamp = date('Y-m-d-H-i');
                    $fileName = "stock-opname-{$this->record->opname_number}-{$timestamp}.pdf";
                    Storage::disk('public')->put("reports/{$fileName}", $pdf->output());

                    $this->generatedPdfUrl = asset("storage/reports/{$fileName}");
                    $this->generatedPdfFilename = $fileName;
                    $this->generatedPdfTitle = $title;
                })
                ->modalHeading(false)
                ->modalWidth('sm')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalContent(fn () => view('exports.pdf.modal-preview', [
                    'url' => $this->generatedPdfUrl,
                    'filename' => $this->generatedPdfFilename,
                    'title' => $this->generatedPdfTitle,
                ])),
            EditAction::make()
                ->hidden(fn ($record) => $record->status !== 'draft'),
            DeleteAction::make()
                ->hidden(fn ($record) => $record->status !== 'draft'),
        ];
    }
}
