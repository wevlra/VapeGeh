<?php

namespace App\Filament\Admin\Resources\Incomes;

use App\Filament\Admin\Resources\Incomes\Pages\CreateIncome;
use App\Filament\Admin\Resources\Incomes\Pages\EditIncome;
use App\Filament\Admin\Resources\Incomes\Pages\ListIncomes;
use App\Filament\Admin\Resources\Incomes\Pages\ViewIncome;
use App\Filament\Admin\Resources\Incomes\Schemas\IncomeForm;
use App\Filament\Admin\Resources\Incomes\Schemas\IncomeInfolist;
use App\Filament\Admin\Resources\Incomes\Tables\IncomesTable;
use App\Models\Income;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IncomeResource extends Resource
{
    protected static ?string $model = Income::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static \UnitEnum|string|null $navigationGroup = 'Bookkeeping';

    public static function form(Schema $schema): Schema
    {
        return IncomeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncomesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return IncomeInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncomes::route('/'),
            'create' => CreateIncome::route('/create'),
            'edit' => EditIncome::route('/{record}/edit'),
            'view' => ViewIncome::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): string
    {
        $modelClass = static::getModel();

        return $modelClass::query()->count();
    }
}
