<?php

namespace App\Filament\Staff\Resources\Sales\Pages;

use App\Actions\DeleteSale;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Filament\Staff\Resources\Sales\SaleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
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
                ->openUrlInNewTab(),
            Action::make('print_invoice')
                ->label('Cetak Invoice')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url(fn (): string => route('admin.history.invoice', $this->record?->stockMovements()->first()))
                ->openUrlInNewTab(),
            EditAction::make(),
            DeleteAction::make()
                ->action(fn () => app(DeleteSale::class)->execute($this->record))
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }
}
