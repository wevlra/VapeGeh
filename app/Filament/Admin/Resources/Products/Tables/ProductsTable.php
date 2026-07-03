<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->html()
                    ->formatStateUsing(function ($state, Product $record) {
                        $name = e($state);
                        $beli = number_format($record->purchase_price, 0, ',', '.');
                        $jual = number_format($record->selling_price, 0, ',', '.');

                        return <<<HTML
                        <div class="w-full min-w-[200px]">
                            <div class="hidden md:block font-bold">{$name}</div>
                            <div class="flex items-center justify-between w-full md:hidden py-1">
                                <div class="flex flex-col gap-y-1">
                                    <span class="text-sm font-bold leading-6 text-gray-950 dark:text-white">{$name}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Beli: Rp {$beli}</span>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="text-sm font-semibold leading-6 text-primary-600 dark:text-primary-400">Jual: Rp {$jual}</span>
                                </div>
                            </div>
                        </div>
                        HTML;
                    }),
                TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 0, ',', '.'))
                    ->color('primary')
                    ->weight('bold')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->stackedOnMobile()
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->title('Produk dihapus')
                            ->body('Produk telah dihapus permanen.')
                            ->danger()
                    ),
            ]);
    }
}
