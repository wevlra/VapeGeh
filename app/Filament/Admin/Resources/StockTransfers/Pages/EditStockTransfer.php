<?php

namespace App\Filament\Admin\Resources\StockTransfers\Pages;

use App\Filament\Admin\Resources\StockTransfers\Schemas\StockTransferForm;
use App\Filament\Admin\Resources\StockTransfers\StockTransferResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditStockTransfer extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = StockTransferResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->status !== 'pending') {
            Notification::make()
                ->warning()
                ->title('Cannot edit')
                ->body('Only pending transfers can be edited.')
                ->send();

            $this->redirect(static::getResource()::getUrl('index'));
        }
    }

    public function form(Schema $schema): Schema
    {
        return StockTransferForm::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotificationTitle('Stock Transfer deleted successfully'),
        ];
    }
}
