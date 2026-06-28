<?php

namespace App\Filament\Admin\Resources\StockTransfers;

use App\Filament\Admin\Resources\StockTransfers\Pages\CreateStockTransfer;
use App\Filament\Admin\Resources\StockTransfers\Pages\EditStockTransfer;
use App\Filament\Admin\Resources\StockTransfers\Pages\ListStockTransfers;
use App\Filament\Admin\Resources\StockTransfers\Pages\ViewStockTransfer;
use App\Filament\Admin\Resources\StockTransfers\Schemas\StockTransferForm;
use App\Filament\Admin\Resources\StockTransfers\Schemas\StockTransferInfolist;
use App\Filament\Admin\Resources\StockTransfers\Tables\StockTransfersTable;
use App\Models\StockTransfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Transfer Stok';

    protected static \UnitEnum|string|null $navigationGroup = 'Inventaris';

    public static function getModelLabel(): string
    {
        return 'Transfer Stok';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Transfer Stok';
    }

    public static function form(Schema $schema): Schema
    {
        return StockTransferForm::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['fromLocation', 'toLocation', 'creator']);
    }

    public static function table(Table $table): Table
    {
        return StockTransfersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockTransferInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockTransfers::route('/'),
            'create' => CreateStockTransfer::route('/create'),
            'edit' => EditStockTransfer::route('/{record}/edit'),
            'view' => ViewStockTransfer::route('/{record}'),
        ];
    }
}
