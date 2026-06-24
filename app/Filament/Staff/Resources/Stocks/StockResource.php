<?php

namespace App\Filament\Staff\Resources\Stocks;

use App\Filament\Staff\Resources\Stocks\Pages\ListStocks;
use App\Filament\Staff\Resources\Stocks\Tables\StocksTable;
use App\Models\Stock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Inventory';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('location_id', auth()->user()->location_id);
    }

    public static function table(Table $table): Table
    {
        return StocksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStocks::route('/'),
        ];
    }
}
