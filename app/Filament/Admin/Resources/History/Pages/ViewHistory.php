<?php

namespace App\Filament\Admin\Resources\History\Pages;

use App\Filament\Actions\PrintInvoiceAction;
use App\Filament\Actions\PrintReceiptAction;
use App\Filament\Admin\Resources\History\HistoryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewHistory extends ViewRecord
{
    protected static string $resource = HistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PrintReceiptAction::make('print_receipt'),
            PrintInvoiceAction::make('print_invoice'),
        ];
    }
}
