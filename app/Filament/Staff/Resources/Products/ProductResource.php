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

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static \UnitEnum|string|null $navigationGroup = 'Inventory';

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'view' => ViewProduct::route('/{record}'),
        ];
    }
}
