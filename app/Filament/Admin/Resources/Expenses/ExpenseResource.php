<?php

namespace App\Filament\Admin\Resources\Expenses;

use App\Filament\Admin\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Admin\Resources\Expenses\Pages\EditExpense;
use App\Filament\Admin\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Admin\Resources\Expenses\Pages\ViewExpense;
use App\Filament\Admin\Resources\Expenses\Schemas\ExpenseForm;
use App\Filament\Admin\Resources\Expenses\Schemas\ExpenseInfolist;
use App\Filament\Admin\Resources\Expenses\Tables\ExpensesTable;
use App\Models\Expense;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'Pengeluaran';

    protected static \UnitEnum|string|null $navigationGroup = 'Pembukuan';

    public static function getModelLabel(): string
    {
        return 'Pengeluaran';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pengeluaran';
    }

    public static function form(Schema $schema): Schema
    {
        return ExpenseForm::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['location', 'creator']);
    }

    public static function table(Table $table): Table
    {
        return ExpensesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExpenseInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
            'view' => ViewExpense::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): string
    {
        $modelClass = static::getModel();

        return $modelClass::query()->count();
    }
}
