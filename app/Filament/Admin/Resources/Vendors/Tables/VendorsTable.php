<?php

namespace App\Filament\Admin\Resources\Vendors\Tables;

use App\Filament\Admin\Resources\Vendors\VendorResource;
use App\Models\Vendor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Vendor $record): string => VendorResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_person')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products'),
            ])
            ->filters([
                SelectFilter::make('products')
                    ->label('Products')
                    ->options([
                        'with' => 'With Products',
                        'without' => 'No Products',
                    ])
                    ->query(fn ($query, $state) => match ($state) {
                        'with' => $query->has('products'),
                        'without' => $query->doesntHave('products'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
