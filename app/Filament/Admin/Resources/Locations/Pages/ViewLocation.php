<?php

namespace App\Filament\Admin\Resources\Locations\Pages;

use App\Actions\DeleteLocation;
use App\Exports\StockReportExport;
use App\Filament\Admin\Resources\Locations\LocationResource;
use App\Filament\Admin\Resources\Locations\RelationManagers\StocksRelationManager;
use App\Filament\Concerns\HasExportActions;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Stock;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLocation extends ViewRecord
{
    use HasExportActions;
    use NotifiesWithDetail;

    protected static string $resource = LocationResource::class;

    public function buildExportQuery()
    {
        return Stock::query()
            ->where('location_id', $this->record->id)
            ->with(StockReportExport::getEagerLoads());
    }

    public function getExportTitle(): string
    {
        return 'Stok Lokasi: '.$this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getExportActionGroup(
                StockReportExport::class,
                'exports.pdf.location-stock',
                'stok-lokasi-'.str($this->record->name)->slug()
            ),
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
