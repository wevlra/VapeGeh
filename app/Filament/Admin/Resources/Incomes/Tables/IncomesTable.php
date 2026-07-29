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
                        'service' => 'Jasa / Service',
                        'cashback' => 'Cashback & Rebate',
                        'opname' => 'Penyesuaian Stok',
                        'investment' => 'Modal / Investasi',
                        'other' => 'Lainnya',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'sale' => 'success',
                        'debt_payment' => 'info',
                        'service' => 'primary',
                        'cashback' => 'warning',
                        'opname' => 'gray',
                        'investment' => 'success',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'sale' => 'heroicon-o-banknotes',
                        'debt_payment' => 'heroicon-o-receipt-refund',
                        'service' => 'heroicon-o-wrench-screwdriver',
                        'cashback' => 'heroicon-o-gift',
                        'opname' => 'heroicon-o-pencil-square',
                        'investment' => 'heroicon-o-currency-dollar',
                        'other' => 'heroicon-o-folder',
                        default => null,
                    })
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50)
                    ->placeholder('Tidak ada deskripsi'),
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
                TextColumn::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name'),
                SelectFilter::make('category')
                    ->options([
                        'sale' => 'Penjualan',
                        'debt_payment' => 'Pembayaran Hutang',
                        'service' => 'Jasa / Service',
                        'cashback' => 'Cashback & Rebate',
                        'opname' => 'Penyesuaian Stok',
                        'investment' => 'Modal / Investasi',
                        'other' => 'Lainnya',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Income $record): bool => $record->isManual()),
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
