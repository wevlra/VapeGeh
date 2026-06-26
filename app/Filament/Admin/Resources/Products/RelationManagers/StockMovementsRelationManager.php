<?php

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use App\Models\Stock;
use App\Models\StockMovement;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'Stock In',
                        'out' => 'Stock Out',
                        'transfer_in' => 'Transfer In',
                        'transfer_out' => 'Transfer Out',
                        'adjustment' => 'Adjustment',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'transfer_in' => 'info',
                        'transfer_out' => 'warning',
                        'adjustment' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('location.name'),
                TextColumn::make('quantity'),
                TextColumn::make('notes')
                    ->limit(30)
                    ->placeholder('—'),
                TextColumn::make('creator.name')
                    ->label('By'),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (StockMovement $record): bool => ! in_array($record->type, ['in', 'out'], true))
                    ->modalHeading(fn (StockMovement $record): string => $record->type === 'in' ? 'Edit Stock In' : 'Edit Stock Out')
                    ->form([
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->formatStateUsing(fn (StockMovement $record): int => abs((int) $record->quantity))
                            ->dehydrateStateUsing(fn (?int $state): int => $state ?? 0),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->formatStateUsing(fn (StockMovement $record): ?string => $record->notes),
                    ])
                    ->action(function (StockMovement $record, array $data): void {
                        $oldQty = abs((int) $record->quantity);
                        $newQty = (int) $data['quantity'];

                        if ($newQty === $oldQty) {
                            $record->update(['notes' => $data['notes'] ?? null]);

                            Notification::make()
                                ->title('Notes updated')
                                ->success()
                                ->send();

                            return;
                        }

                        $difference = $newQty - $oldQty;

                        DB::transaction(function () use ($record, $difference, $newQty, $data) {
                            $stock = Stock::where('product_id', $record->product_id)
                                ->where('location_id', $record->location_id)
                                ->lockForUpdate()
                                ->first();

                            if ($record->type === 'in') {
                                if ($difference > 0) {
                                    $stock?->increment('qty', $difference);
                                } else {
                                    $stock?->decrement('qty', abs($difference));
                                }
                                $record->update([
                                    'quantity' => $newQty,
                                    'notes' => $data['notes'] ?? null,
                                ]);
                            } else {
                                if ($difference > 0) {
                                    $stock?->decrement('qty', $difference);
                                } else {
                                    $stock?->increment('qty', abs($difference));
                                }
                                $record->update([
                                    'quantity' => -$newQty,
                                    'notes' => $data['notes'] ?? null,
                                ]);
                            }
                        });

                        Notification::make()
                            ->title($record->type === 'in' ? 'Stock in updated' : 'Stock out updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
