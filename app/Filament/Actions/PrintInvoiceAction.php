<?php

namespace App\Filament\Actions;

use App\Models\StockMovement;
use Filament\Actions\Action;
use Spatie\LaravelPdf\Facades\Pdf;

class PrintInvoiceAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Print Invoice')
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->action(function (StockMovement $record) {
                $record->load(['product', 'location', 'creator', 'buyer', 'related']);
                return Pdf::view('invoices.invoice', ['movement' => $record])
                    ->name('invoice-'.($record->related?->invoice_number ?? 'SM-'.$record->id).'.pdf')
                    ->download();
            })
            ->hidden(fn (StockMovement $record): bool => $record->type !== 'out');
    }
}
