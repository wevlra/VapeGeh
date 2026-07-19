<?php

namespace App\Filament\Admin\Resources\History\Pages;

use App\Filament\Actions\PrintInvoiceAction;
use App\Filament\Actions\PrintReceiptAction;
use App\Filament\Actions\PrintReportAction;
use App\Filament\Admin\Pages\EditStockOut;
use App\Filament\Admin\Resources\History\HistoryResource;
use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Models\Sale;
use App\Models\StockEntry;
use App\Models\StockMovement;
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
            PrintReportAction::make('print_report'),
            Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->color('info')
                ->url(function (StockMovement $record) {
                    if ($record->related instanceof Sale) {
                        return SaleResource::getUrl('edit', ['record' => $record->related_id]);
                    }
                    if ($record->related instanceof StockEntry && $record->type === 'out') {
                        return EditStockOut::getUrl(['record' => $record->related_id]);
                    }

                    return null;
                })
                ->visible(fn (StockMovement $record) => $record->related instanceof Sale ||
                    ($record->related instanceof StockEntry && $record->type === 'out')
                ),
        ];
    }
}
