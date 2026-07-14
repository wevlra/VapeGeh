<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Concerns\NotifiesWithDetail;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'admin' => 'heroicon-o-shield-check',
                        'staff' => 'heroicon-o-user',
                        default => 'heroicon-o-user',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'primary',
                        'staff' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Admin',
                        'staff' => 'Staf',
                        default => ucfirst($state),
                    })
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'inactive' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-minus',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'staff' => 'Staf',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->id === auth()->id())
                    ->successNotification(
                        NotifiesWithDetail::buildErrorNotification(
                            'Data Berhasil Dihapus',
                            'Data pengguna/staf telah berhasil dihapus dari sistem.'
                        )
                    ),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
