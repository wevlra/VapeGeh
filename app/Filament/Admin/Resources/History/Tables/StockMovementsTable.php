<?php

namespace App\Filament\Admin\Resources\History\Tables;

use App\Filament\Actions\PrintInvoiceAction;
use App\Filament\Actions\PrintReceiptAction;
use App\Filament\Admin\Resources\History\StockMovementResource;
use App\Models\Sale;
use App\Models\StockMovement;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (StockMovement $record): string => StockMovementResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Date'),
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
                        'in', 'transfer_in' => 'success',
                        'out', 'transfer_out' => 'danger',
                        'adjustment' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (StockMovement $record): string => $record->related instanceof Sale
                        ? 'Sale ('.$record->related->invoice_number.')'
                        : ($record->product?->name ?? '-')),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->formatStateUsing(fn (StockMovement $record): string => $record->quantity > 0 ? '+'.$record->quantity : (string) $record->quantity)
                    ->color(fn (StockMovement $record): string => $record->quantity > 0 ? 'success' : 'danger'),
                TextColumn::make('creator.name')
                    ->label('Staff')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'in' => 'Stock In',
                        'out' => 'Stock Out',
                        'transfer_in' => 'Transfer In',
                        'transfer_out' => 'Transfer Out',
                        'adjustment' => 'Adjustment',
                    ]),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name'),
                Filter::make('created_at')
                    ->form([DatePicker::make('from'), DatePicker::make('until')])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                PrintReceiptAction::make('print_receipt'),
                PrintInvoiceAction::make('print_invoice'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
