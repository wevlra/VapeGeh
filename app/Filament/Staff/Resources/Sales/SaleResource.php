<?php

namespace App\Filament\Staff\Resources\Sales;

use App\Filament\Staff\Resources\Sales\Pages\ListSales;
use App\Filament\Staff\Resources\Sales\Schemas\SaleForm;
use App\Filament\Staff\Resources\Sales\Tables\SalesTable;
use App\Models\Sale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static \UnitEnum|string|null $navigationGroup = 'Sales';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('location_id', auth()->user()->location_id);
    }

    public static function form(Schema $schema): Schema
    {
        return SaleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSales::route('/'),
        ];
    }

    public static function getNavigationBadge(): string
    {
        $modelClass = static::getModel();

        return $modelClass::where('location_id', auth()->user()->location_id)->count();
    }
}
