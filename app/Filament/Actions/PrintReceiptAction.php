<?php

namespace App\Filament\Actions;

use App\Models\StockMovement;
use Filament\Actions\Action;

class PrintReceiptAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Print Receipt')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url(fn (StockMovement $record): string => route('admin.stock-movements.receipt', $record))
            ->openUrlInNewTab();
    }
}
