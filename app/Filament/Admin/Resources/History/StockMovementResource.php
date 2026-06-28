<?php

namespace App\Filament\Admin\Resources\History;

use App\Filament\Admin\Resources\History\Pages\ListStockMovements;
use App\Filament\Admin\Resources\History\Pages\ViewStockMovement;
use App\Filament\Admin\Resources\History\Schemas\StockMovementInfolist;
use App\Filament\Admin\Resources\History\Tables\StockMovementsTable;
use App\Models\Sale;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'History';

    protected static \UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query) {
                // Non-Sale movements: show all
                $query->where('related_type', '!=', Sale::class)
                    // Sale movements: only show the first (min id) per related_id
                    ->orWhere(function (Builder $q) {
                        $q->where('related_type', Sale::class)
                            ->whereIn('id', function ($sub) {
                                $sub->selectRaw('MIN(id)')
                                    ->from('stock_movements')
                                    ->where('related_type', Sale::class)
                                    ->groupBy('related_id');
                            });
                    });
            });
    }

    public static function table(Table $table): Table
    {
        return StockMovementsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockMovementInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
            'view' => ViewStockMovement::route('/{record}'),
        ];
    }
}
