<?php

namespace App\Filament\Admin\Resources\Expenses\Tables;

use App\Filament\Admin\Resources\Expenses\ExpenseResource;
use App\Models\Expense;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Expense $record): string => ExpenseResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('location.name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'purchase' => 'warning',
                        'salary' => 'primary',
                        'utilities' => 'info',
                        'transport' => 'gray',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'purchase' => 'heroicon-o-shopping-cart',
                        'salary' => 'heroicon-o-user-group',
                        'utilities' => 'heroicon-o-bolt',
                        'transport' => 'heroicon-o-truck',
                        'other' => 'heroicon-o-folder',
                        default => null,
                    })
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Created by'),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name'),
                SelectFilter::make('category')
                    ->options([
                        'purchase' => 'Purchase',
                        'salary' => 'Salary',
                        'utilities' => 'Utilities',
                        'transport' => 'Transport',
                        'other' => 'Other',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->title('Expense deleted')
                            ->body('The expense record has been permanently removed.')
                            ->danger()
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
