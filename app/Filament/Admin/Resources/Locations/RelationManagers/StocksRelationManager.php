<?php

namespace App\Filament\Admin\Resources\Locations\RelationManagers;

use App\Actions\AdjustStock;
use App\Models\Stock;
use Filament\Actions\EditAction as TableEditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('qty')
                    ->label('Quantity')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
            ])
            ->defaultSort('product.name')
            ->recordActions([
                TableEditAction::make()
                    ->modalHeading('Adjust Stock')
                    ->form([
                        TextInput::make('qty')
                            ->label('Quantity')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(fn (Stock $record): int => $record->qty),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->maxLength(1000),
                    ])
                    ->action(function (Stock $record, array $data): void {
                        app(AdjustStock::class)->execute(
                            $record,
                            (int) $data['qty'],
                            $data['notes'] ?? null,
                        );

                        Notification::make()
                            ->title('Stock adjusted')
                            ->body('Quantity for '.$record->product->name.' has been set to '.$data['qty'].' units.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
