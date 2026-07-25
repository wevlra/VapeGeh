<?php

namespace App\Filament\Admin\Resources\History;

use App\Filament\Admin\Resources\History\Pages\ListHistory;
use App\Filament\Admin\Resources\History\Pages\ViewHistory;
use App\Filament\Admin\Resources\History\Schemas\HistoryInfolist;
use App\Filament\Admin\Resources\History\Tables\HistoryTable;
use App\Models\Sale;
use App\Models\StockEntry;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use UnitEnum;

class HistoryResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $slug = 'history';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Riwayat';

    protected static UnitEnum|string|null $navigationGroup = 'Inventaris';

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return 'Riwayat';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Riwayat';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'product',
                'location',
                'creator',
                'related' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        Sale::class => ['items.product', 'buyer'],
                        StockEntry::class => ['items.product', 'buyer'],
                        StockOpname::class => ['items.product'],
                        StockTransfer::class => ['items.product'],
                    ]);
                },
            ])
            ->whereIn('id', function ($sub) {
                $sub->selectRaw('MIN(id)')
                    ->from('stock_movements')
                    ->groupBy(['related_type', 'related_id', 'location_id']);
            })
            ->where(function ($query) {
                $query->where('type', '!=', 'transfer')
                    ->orWhere(function ($q) {
                        $q->where('type', 'transfer')
                            ->where('quantity', '>', 0);
                    });
            });
    }

    public static function table(Table $table): Table
    {
        return HistoryTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HistoryInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHistory::route('/'),
            'view' => ViewHistory::route('/{record}'),
        ];
    }
}
