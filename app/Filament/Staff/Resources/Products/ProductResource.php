<?php

namespace App\Filament\Staff\Resources\Products;

use App\Filament\Staff\Resources\Products\Pages\ListProducts;
use App\Filament\Staff\Resources\Products\Pages\ViewProduct;
use App\Filament\Staff\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Staff\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static \UnitEnum|string|null $navigationGroup = 'Inventaris';

    public static function getModelLabel(): string
    {
        return 'Produk';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Produk';
    }

    public static function getEloquentQuery(): Builder
    {
        $locationId = auth()->user()?->location_id;

        return parent::getEloquentQuery()
            ->with(['stocks' => fn ($q) => $q->where('location_id', $locationId)])
            ->whereHas('stocks', fn (Builder $q) => $q->where('location_id', $locationId));
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function getNavigationBadge(): string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'view' => ViewProduct::route('/{record}'),
        ];
    }
}
