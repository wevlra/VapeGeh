<?php

namespace App\Filament\Admin\Resources\Locations;

use App\Filament\Admin\Resources\Locations\Pages\CreateLocation;
use App\Filament\Admin\Resources\Locations\Pages\EditLocation;
use App\Filament\Admin\Resources\Locations\Pages\ListLocations;
use App\Filament\Admin\Resources\Locations\Pages\ViewLocation;
use App\Filament\Admin\Resources\Locations\Schemas\LocationForm;
use App\Filament\Admin\Resources\Locations\Schemas\LocationInfolist;
use App\Filament\Admin\Resources\Locations\Tables\LocationsTable;
use App\Models\Location;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static \UnitEnum|string|null $navigationGroup = 'Management';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'store');
    }

    public static function form(Schema $schema): Schema
    {
        return LocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StocksRelationManager::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return LocationInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit' => EditLocation::route('/{record}/edit'),
            'view' => ViewLocation::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): string
    {
        $modelClass = static::getModel();

        return $modelClass::query()->count();
    }
}
