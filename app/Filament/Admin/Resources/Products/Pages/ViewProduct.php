<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Admin\Resources\Products\RelationManagers\StockMovementsRelationManager;
use App\Filament\Admin\Resources\Products\RelationManagers\StockRelationManager;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            StockRelationManager::class,
            StockMovementsRelationManager::class,
        ];
    }
}
