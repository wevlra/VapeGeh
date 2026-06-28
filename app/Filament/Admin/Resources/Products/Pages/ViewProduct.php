<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Admin\Resources\Products\RelationManagers\HistoryRelationManager;
use App\Filament\Admin\Resources\Products\RelationManagers\StockRelationManager;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    use NotifiesWithDetail;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            StockRelationManager::class,
            HistoryRelationManager::class,
        ];
    }
}
