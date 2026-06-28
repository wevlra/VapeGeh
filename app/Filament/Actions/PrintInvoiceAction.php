<?php

namespace App\Filament\Actions;

use App\Models\StockMovement;
use Filament\Actions\Action;

class PrintInvoiceAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Print Invoice')
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->url(fn (StockMovement $record): string => route('admin.stock-movements.invoice', $record))
            ->openUrlInNewTab()
            ->hidden(fn (StockMovement $record): bool => $record->type !== 'out');
    }
}
