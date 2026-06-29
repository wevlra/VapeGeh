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

class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    protected static ?string $title = 'Riwayat';

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
                    ->label('Tanggal')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
                        'transfer' => 'Transfer',
                        'adjustment' => 'Penyesuaian',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'transfer' => 'info',
                        'adjustment' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'in' => 'heroicon-o-arrow-down-tray',
                        'out' => 'heroicon-o-arrow-up-tray',
                        'transfer' => 'heroicon-o-arrow-right-end-on-rectangle',
                        'adjustment' => 'heroicon-o-pencil',
                        default => null,
                    }),
                TextColumn::make('location.name')
                    ->label('Lokasi'),
                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->formatStateUsing(fn (StockMovement $record): string => $record->quantity > 0 ? '+'.$record->quantity : (string) $record->quantity)
                    ->color(fn (StockMovement $record): string => $record->quantity > 0 ? 'success' : 'danger'),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->placeholder('—'),
                TextColumn::make('creator.name')
                    ->label('Oleh'),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (StockMovement $record): bool => ! in_array($record->type, ['in', 'out'], true))
                    ->modalHeading(fn (StockMovement $record): string => $record->type === 'in' ? 'Edit Stok Masuk' : 'Edit Stok Keluar')
                    ->form([
                        TextInput::make('quantity')
                            ->label('Jumlah')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->formatStateUsing(fn (StockMovement $record): int => abs((int) $record->quantity))
                            ->dehydrateStateUsing(fn (?int $state): int => $state ?? 0),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->formatStateUsing(fn (StockMovement $record): ?string => $record->notes),
                    ])
                    ->action(function (StockMovement $record, array $data): void {
                        $oldQty = abs((int) $record->quantity);
                        $newQty = (int) $data['quantity'];

                        if ($newQty === $oldQty) {
                            $record->update(['notes' => $data['notes'] ?? null]);

                            Notification::make()
                                ->title('Catatan diperbarui')
                                ->body('Catatan untuk pergerakan ini berhasil diperbarui.')
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
                            ->title($record->type === 'in' ? 'Stok masuk diperbarui' : 'Stok keluar diperbarui')
                            ->body($record->type === 'in'
                                ? 'Catatan stok masuk telah diperbarui menjadi '.$newQty.' unit.'
                                : 'Catatan stok keluar telah diperbarui menjadi '.$newQty.' unit.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
