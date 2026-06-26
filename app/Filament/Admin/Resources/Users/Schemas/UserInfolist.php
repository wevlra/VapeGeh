<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('User Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('role')
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
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        TextEntry::make('status')
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
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        TextEntry::make('location.name')
                            ->label('Location')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
