<?php

namespace App\Filament\Admin\Resources\StockTransfers\Pages;

use App\Filament\Admin\Resources\StockTransfers\Schemas\StockTransferForm;
use App\Filament\Admin\Resources\StockTransfers\StockTransferResource;
use App\Filament\Concerns\NotifiesWithDetail;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateStockTransfer extends CreateRecord
{
    use NotifiesWithDetail;

    protected static string $resource = StockTransferResource::class;

    protected static bool $canCreateAnother = false;

    public function form(Schema $schema): Schema
    {
        return StockTransferForm::configure($schema);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
