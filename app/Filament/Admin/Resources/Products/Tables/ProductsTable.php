<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('view', ['record' => $record]))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('name')
                            ->weight('bold')
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('purchase_price')
                            ->formatStateUsing(fn ($state) => 'Beli: Rp ' . number_format($state, 0, ',', '.'))
                            ->color('gray')
                            ->sortable(),
                    ]),
                    Stack::make([
                        TextColumn::make('selling_price')
                            ->formatStateUsing(fn ($state) => 'Jual: Rp ' . number_format($state, 0, ',', '.'))
                            ->color('primary')
                            ->weight('bold')
                            ->alignEnd()
                            ->sortable(),
                    ])->space(1),
                ])
            ])
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
