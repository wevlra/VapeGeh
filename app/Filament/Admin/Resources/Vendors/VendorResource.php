<?php

namespace App\Filament\Admin\Resources\Vendors;

use App\Filament\Admin\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Admin\Resources\Vendors\Pages\EditVendor;
use App\Filament\Admin\Resources\Vendors\Pages\ListVendors;
use App\Filament\Admin\Resources\Vendors\Schemas\VendorForm;
use App\Filament\Admin\Resources\Vendors\Tables\VendorsTable;
use App\Models\Vendor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static \UnitEnum|string|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return VendorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): string
    {
        $modelClass = static::getModel();

        return $modelClass::query()->count();
    }
}
