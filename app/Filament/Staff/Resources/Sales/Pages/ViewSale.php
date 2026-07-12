<?php

namespace App\Filament\Staff\Resources\Sales\Pages;

use App\Filament\Concerns\NotifiesWithDetail;
use App\Filament\Staff\Resources\Sales\SaleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    use NotifiesWithDetail;

    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_receipt')
                ->label('Cetak Nota')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('admin.sales.receipt', $this->record))
                ->openUrlInNewTab()
                ->extraAttributes(function () {
                    return [
                        'x-on:click' => "
                            if (window.Capacitor && window.Capacitor.isNativePlatform()) {
                                \$event.preventDefault();
                                const url = '".route('admin.sales.receipt', ['sale' => $this->record->id, 'format' => 'text'])."';
                                
                                const savedPrinter = localStorage.getItem('savedPrinter');
                                if (savedPrinter) {
                                    window.dispatchEvent(new CustomEvent('print-native', { detail: { url } }));
                                } else {
                                    window.dispatchEvent(new CustomEvent('open-printer-modal', { detail: { url } }));
                                }
                            }
                        ",
                    ];
                }),
        ];
    }
}
