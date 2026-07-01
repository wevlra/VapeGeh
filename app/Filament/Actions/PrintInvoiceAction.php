<?php

namespace App\Filament\Actions;

use App\Models\StockMovement;
use Filament\Actions\Action;

class PrintInvoiceAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cetak Invoice')
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->hidden(fn (StockMovement $record): bool => $record->type !== 'out');
    }
}
