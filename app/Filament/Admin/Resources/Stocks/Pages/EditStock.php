<?php

namespace App\Filament\Admin\Resources\Stocks\Pages;

use App\Filament\Admin\Resources\Stocks\StockResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStock extends EditRecord
{
    use NotifiesWithDetail;

    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotificationTitle('Stock deleted successfully'),
        ];
    }
}
