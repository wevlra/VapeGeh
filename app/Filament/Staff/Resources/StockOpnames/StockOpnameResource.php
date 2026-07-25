<?php

namespace App\Filament\Staff\Resources\StockOpnames;

use App\Filament\Admin\Resources\StockOpnames\Schemas\StockOpnameInfolist;
use App\Filament\Admin\Resources\StockOpnames\Tables\StockOpnamesTable;
use App\Filament\Staff\Resources\StockOpnames\Pages\ListStockOpnames;
use App\Filament\Staff\Resources\StockOpnames\Pages\ViewStockOpname;
use App\Models\StockOpname;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Penyesuaian Stok';

    protected static ?string $pluralModelLabel = 'Penyesuaian Stok';

    protected static ?string $modelLabel = 'Penyesuaian Stok';

    protected static UnitEnum|string|null $navigationGroup = 'Manajemen';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function canViewAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['location', 'creator', 'items.product'])
            ->where('location_id', auth()->user()->location_id)
            ->where('status', 'completed');
    }

    public static function table(Table $table): Table
    {
        return StockOpnamesTable::configure($table)
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockOpnameInfolist::configure($schema);
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
            'index' => ListStockOpnames::route('/'),
            'view' => ViewStockOpname::route('/{record}'),
        ];
    }
}
