<?php

namespace App\Filament\Admin\Resources\Locations\Pages;

use App\Actions\DeleteLocation;
use App\Filament\Admin\Resources\Locations\LocationResource;
use App\Filament\Admin\Resources\Locations\RelationManagers\StocksRelationManager;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLocation extends ViewRecord
{
    use NotifiesWithDetail;

    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteLocation::make()
                ->successNotification(fn (Notification $notification): Notification => $this->getDeletedNotification()),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            StocksRelationManager::class,
        ];
    }
}
