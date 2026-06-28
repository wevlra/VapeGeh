<?php

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use App\Actions\AdjustStock;
use App\Models\Stock;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StockRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('qty')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),
            ])
            ->recordActions([
                EditAction::make()
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
                            ->rows(2),
                    ])
                    ->action(function (Stock $record, array $data): void {
                        app(AdjustStock::class)->execute(
                            $record,
                            (int) $data['qty'],
                            $data['notes'] ?? null,
                        );

                        Notification::make()
                            ->title('Stock adjusted')
                            ->body('Quantity for '.$record->location->name.' has been set to '.$data['qty'].' units.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
