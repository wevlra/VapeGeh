<?php

namespace App\Filament\Admin\Resources\Sales;

use App\Filament\Admin\Resources\Sales\Pages\EditSale;
use App\Filament\Admin\Resources\Sales\Pages\ListSales;
use App\Filament\Admin\Resources\Sales\Pages\ViewSale;
use App\Filament\Admin\Resources\Sales\Schemas\SaleForm;
use App\Filament\Admin\Resources\Sales\Schemas\SaleInfolist;
use App\Filament\Admin\Resources\Sales\Tables\SalesTable;
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

    protected static ?string $navigationLabel = 'Penjualan';

    protected static \UnitEnum|string|null $navigationGroup = 'Penjualan';

    public static function getModelLabel(): string
    {
        return 'Penjualan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Penjualan';
    }

    public static function table(Table $table): Table
    {
        return SalesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['items.product']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SaleInfolist::configure($schema);
    }

    public static function form(Schema $schema): Schema
    {
        return SaleForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSales::route('/'),
            'view' => ViewSale::route('/{record}'),
            'edit' => EditSale::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): string
    {
        $modelClass = static::getModel();

        return $modelClass::query()->count();
    }
}
