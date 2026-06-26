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
            Action::make('complete')
                ->label('Complete')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Complete Stock Transfer')
                ->modalDescription(fn (StockTransfer $record): string => "Are you sure you want to complete transfer {$record->transfer_number}?")
                ->hidden(fn (StockTransfer $record): bool => $record->status !== 'pending')
                ->action(function (StockTransfer $record): void {
                    app(CompleteStockTransfer::class)->execute($record, auth()->user());

                    Notification::make()
                        ->title('Transfer completed')
                        ->success()
                        ->send();
                }),
            Action::make('cancel')
                ->label('Cancel')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Cancel Stock Transfer')
                ->modalDescription(fn (StockTransfer $record): string => "Cancel transfer {$record->transfer_number}? This will not affect stock.")
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

                        $record->update(['status' => 'cancelled']);
                    });

                    Notification::make()
                        ->title('Transfer cancelled')
                        ->success()
                        ->send();
                }),
            DeleteAction::make()
                ->successNotificationTitle('Stock Transfer deleted successfully'),
        ];
    }
}
