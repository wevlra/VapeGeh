<?php

namespace App\Filament\Actions;

use App\Models\Sale;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;

class PrintSaleInvoiceAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cetak Invoice')
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->action(function () {
                // Handled by mountUsing and modal
            })
            ->mountUsing(function (Sale $record) {
                $stockMovement = $record->stockMovements()->where('type', 'out')->first();

                if (! $stockMovement) {
                    return;
                }

                $invoiceService = app(InvoiceService::class);
                $invoice = $invoiceService->generate($stockMovement);

                $timestamp = date('Y-m-d-H-i');
                $fileName = 'invoice-'.$record->invoice_number.'-'.$timestamp.'.pdf';

                ob_start();
                $invoice->stream()->sendContent();
                $content = ob_get_clean();

                Storage::disk('public')->put("reports/{$fileName}", $content);

                $url = asset("storage/reports/{$fileName}");

                session()->put("sale_invoice_url_{$record->id}", $url);
                session()->put("sale_invoice_filename_{$record->id}", $fileName);
            })
            ->modalHeading(false)
            ->modalWidth('sm')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(fn (Sale $record) => view('exports.pdf.modal-preview', [
                'url' => session()->get("sale_invoice_url_{$record->id}"),
                'filename' => session()->get("sale_invoice_filename_{$record->id}"),
                'title' => 'Invoice',
            ]));
    }
}
