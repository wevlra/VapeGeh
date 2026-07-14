<?php

namespace App\Filament\Actions;

use App\Models\StockMovement;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;

class PrintInvoiceAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cetak Invoice')
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->hidden(fn (StockMovement $record): bool => $record->type !== 'out')
            ->action(function () {
                // Di-handle oleh mountUsing dan modal
            })
            ->mountUsing(function (StockMovement $record) {
                $invoiceService = app(InvoiceService::class);
                $invoice = $invoiceService->generate($record);

                $timestamp = date('Y-m-d-H-i');
                $fileName = "invoice-{$record->id}-{$timestamp}.pdf";

                // capture stream content
                ob_start();
                $invoice->stream()->sendContent();
                $content = ob_get_clean();

                Storage::disk('public')->put("reports/{$fileName}", $content);

                $url = asset("storage/reports/{$fileName}");

                session()->put("invoice_url_{$record->id}", $url);
                session()->put("invoice_filename_{$record->id}", $fileName);
            })
            ->modalHeading(false)
            ->modalWidth('sm')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(fn (StockMovement $record) => view('exports.pdf.modal-preview', [
                'url' => session()->get("invoice_url_{$record->id}"),
                'filename' => session()->get("invoice_filename_{$record->id}"),
                'title' => 'Invoice',
            ]));
    }
}
