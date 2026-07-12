<?php

namespace App\Filament\Admin\Resources\StockOpnames;

use App\Filament\Admin\Resources\StockOpnames\Pages\ListStockOpnames;
use App\Filament\Admin\Resources\StockOpnames\Pages\ViewStockOpname;
use App\Filament\Admin\Resources\StockOpnames\Schemas\StockOpnameForm;
use App\Filament\Admin\Resources\StockOpnames\Schemas\StockOpnameInfolist;
use App\Filament\Admin\Resources\StockOpnames\Tables\StockOpnamesTable;
use App\Models\StockOpname;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Penyesuaian Stok';

    protected static ?string $pluralModelLabel = 'Penyesuaian Stok';

    protected static ?string $modelLabel = 'Penyesuaian Stok';

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen';

    public static function form(Schema $schema): Schema
    {
        return StockOpnameForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockOpnamesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockOpnameInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['location', 'creator', 'items.product']);
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
            'create' => Pages\StockOpnameCreate::route('/create'),
            'edit' => Pages\StockOpnameEdit::route('/{record}/edit'),
            'view' => ViewStockOpname::route('/{record}'),
        ];
    }
}
