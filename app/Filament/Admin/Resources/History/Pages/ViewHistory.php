<?php

namespace App\Filament\Admin\Resources\History\Pages;

use App\Filament\Actions\PrintInvoiceAction;
use App\Filament\Actions\PrintReceiptAction;
use App\Filament\Admin\Resources\History\HistoryResource;
use App\Models\StockMovement;
use App\Models\StockOpname;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewHistory extends ViewRecord
{
    protected static string $resource = HistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PrintReceiptAction::make('print_receipt'),
            PrintInvoiceAction::make('print_invoice'),
            Action::make('print_opname')
                ->label('Cetak Laporan Penyesuaian')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (StockMovement $record): string => route('admin.stock-opnames.print', $record->related))
                ->openUrlInNewTab()
                ->visible(fn (StockMovement $record): bool => $record->related instanceof StockOpname),
        ];
    }
}
