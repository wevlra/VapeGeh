<?php

namespace App\Filament\Admin\Resources\Incomes\Tables;

use App\Filament\Admin\Resources\Incomes\IncomeResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Income;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sale' => 'Penjualan',
                        'debt_payment' => 'Pembayaran Hutang',
                        'other' => 'Lainnya',
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
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->formatStateUsing(fn (float $state): string => 'Rp '.number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Dibuat oleh'),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name'),
                SelectFilter::make('category')
                    ->options([
                        'sale' => 'Penjualan',
                        'debt_payment' => 'Pembayaran Hutang',
                        'other' => 'Lainnya',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->successNotification(
                        NotifiesWithDetail::buildErrorNotification(
                            'Data Berhasil Dihapus',
                            'Data pendapatan telah berhasil dihapus dari sistem.'
                        )
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
