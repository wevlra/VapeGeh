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
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('qty')
                    ->label('Jumlah')
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
                    ->modalHeading('Sesuaikan Stok')
                    ->form([
                        TextInput::make('qty')
                            ->label('Jumlah')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(fn (Stock $record): int => $record->qty),
                        Textarea::make('notes')
                            ->label('Catatan')
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
                            ->title('Stok disesuaikan')
                            ->body('Jumlah untuk '.$record->product->name.' telah disetel menjadi '.$data['qty'].' unit.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
