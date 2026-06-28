<?php

namespace App\Filament\Admin\Resources\Sales\Pages;

use App\Actions\DeleteSale;
use App\Actions\UpdateSale;
use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Sale;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSale extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(fn () => app(DeleteSale::class)->execute($this->record))
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $sale = Sale::with('items')->findOrFail($data['id']);
        $data['items'] = $sale->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'qty' => $item->qty,
            'price' => (float) $item->price,
        ])->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        app(UpdateSale::class)->execute($record, $data);

        return $record->fresh();
    }
}
