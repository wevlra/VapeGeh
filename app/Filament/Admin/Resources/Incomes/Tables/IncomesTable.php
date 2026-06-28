<?php

namespace App\Filament\Admin\Resources\Incomes\Tables;

use App\Filament\Admin\Resources\Incomes\IncomeResource;
use App\Models\Income;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IncomesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Income $record): string => IncomeResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('location.name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'debt_payment' => 'Debt Payment',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'sale' => 'success',
                        'debt_payment' => 'info',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'sale' => 'heroicon-o-banknotes',
                        'debt_payment' => 'heroicon-o-receipt-refund',
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
                        'sale' => 'Sale',
                        'debt_payment' => 'Debt Payment',
                        'other' => 'Other',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->title('Income deleted')
                            ->body('The income record has been permanently removed.')
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
