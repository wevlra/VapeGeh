<?php

namespace App\Filament\Actions;

use App\Models\StockMovement;
use Filament\Actions\Action;

class PrintReceiptAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cetak Nota')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url(fn (StockMovement $record): string => route('admin.history.receipt', $record))
            ->openUrlInNewTab()
            ->extraAttributes(function (StockMovement $record) {
                return [
                    'x-on:click' => "
                        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
                            \$event.preventDefault();
                            const url = '".route('admin.history.receipt', ['stockMovement' => $record->id, 'format' => 'text'])."';
                            
                            const savedPrinter = localStorage.getItem('savedPrinter');
                            if (savedPrinter) {
                                window.dispatchEvent(new CustomEvent('print-native', { detail: { url } }));
                            } else {
                                window.dispatchEvent(new CustomEvent('open-printer-modal', { detail: { url } }));
                            }
                        }
                    ",
                ];
            });
    }
}
