<?php

namespace App\Filament\Admin\Resources\History\Pages;

use App\Filament\Actions\PrintInvoiceAction;
use App\Filament\Actions\PrintReceiptAction;
use App\Filament\Admin\Resources\History\HistoryResource;
use App\Models\StockMovement;
use Filament\Resources\Pages\ViewRecord;

class ViewHistory extends ViewRecord
{
    protected static string $resource = HistoryResource::class;

    protected function getHeaderActions(): array
    {
        $isTauri = session('__tauri', false);

        $receiptAction = PrintReceiptAction::make('print_receipt');

        if (! $isTauri) {
            $receiptAction
                ->url(fn (StockMovement $record): string => route('admin.history.receipt', $record))
                ->openUrlInNewTab();
        } else {
            $receiptAction->action(function (StockMovement $record): void {
                $this->js("window.dispatchEvent(new CustomEvent('print-receipt-init', { detail: { movementId: {$record->id} } }))");
            });
        }

        return [
            $receiptAction,
            PrintInvoiceAction::make('print_invoice')
                ->url(fn (StockMovement $record): string => route('admin.history.invoice', $record))
                ->openUrlInNewTab(),
        ];
    }
}
