<?php

namespace App\Filament\Admin\Resources\StockTransfers\Pages;

use App\Actions\CompleteStockTransfer;
use App\Filament\Admin\Resources\StockTransfers\Schemas\StockTransferForm;
use App\Filament\Admin\Resources\StockTransfers\StockTransferResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

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
                ->title('Transfer tidak dapat diedit')
                ->body("Transfer \"{$this->record->transfer_number}\" saat ini \"{$this->record->status}\". Hanya transfer tertunda yang dapat diedit.")
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
            Action::make('complete')
                ->label('Selesai')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Transfer Stok')
                ->modalDescription(fn (StockTransfer $record): string => "Yakin ingin menyelesaikan transfer {$record->transfer_number}?")
                ->hidden(fn (StockTransfer $record): bool => $record->status !== 'pending')
                ->action(function (StockTransfer $record): void {
                    app(CompleteStockTransfer::class)->execute($record, auth()->user());

                    Notification::make()
                        ->title('Transfer selesai')
                        ->body("Transfer \"{$record->transfer_number}\" telah selesai. Stok telah dipindahkan antar lokasi.")
                        ->success()
                        ->send();
                }),
            Action::make('cancel')
                ->label('Batal')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Batalkan Transfer Stok')
                ->modalDescription(fn (StockTransfer $record): string => "Batalkan transfer {$record->transfer_number}? Ini tidak akan memengaruhi stok.")
                ->hidden(fn (StockTransfer $record): bool => $record->status !== 'pending')
                ->action(function (StockTransfer $record): void {
                    DB::transaction(function () use ($record) {
                        $record->load('items');

                        foreach ($record->items as $item) {
                            StockMovement::create([
                                'product_id' => $item->product_id,
                                'location_id' => $record->from_location_id,
                                'type' => 'adjustment',
                                'quantity' => 0,
                                'notes' => "Transfer #{$record->transfer_number} cancelled",
                                'related_type' => StockTransfer::class,
                                'related_id' => $record->id,
                                'created_by' => auth()->id(),
                            ]);
                        }

                        $record->status = 'cancelled';
                        $record->save();
                    });

                    Notification::make()
                        ->title('Transfer dibatalkan')
                        ->body("Transfer \"{$record->transfer_number}\" telah dibatalkan. Tidak ada stok yang terpengaruh.")
                        ->warning()
                        ->send();
                }),
            DeleteAction::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }
}
