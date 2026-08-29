<?php

namespace App\Filament\Admin\Resources\Vendors\Tables;

use App\Filament\Admin\Resources\Vendors\VendorResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\Vendor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Vendor $record): string => VendorResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_person')
                    ->label('Kontak Person')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->successNotification(
                        NotifiesWithDetail::buildSuccessNotification(
                            'Data Berhasil Dihapus',
                            'Data vendor telah berhasil dihapus dari sistem.'
                        )
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
