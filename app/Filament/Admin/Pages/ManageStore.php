<?php

namespace App\Filament\Admin\Pages;

use App\Settings\StoreSettings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageStore extends SettingsPage
{
    protected static string $settings = StoreSettings::class;

    protected static ?string $title = 'Pengaturan Toko';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static ?int $navigationSort = 99;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama Toko')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('tagline')
                    ->label('Tagline')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('address')
                    ->label('Alamat')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(50)
                    ->columnSpanFull(),
                Textarea::make('receipt_footer')
                    ->label('Teks Footer Nota')
                    ->helperText('Teks yang muncul di bagian bawah setiap nota cetak.')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public function canEdit(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function getSavedNotificationTitle(): ?string
    {
        return 'Pengaturan toko berhasil disimpan.';
    }
}
