<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('store', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('name', env('STORE_NAME', 'VapeGeh'));
            $blueprint->add('tagline', env('STORE_TAGLINE', 'Your Vaping Partner'));
            $blueprint->add('address', env('STORE_ADDRESS', 'Perum Taman Niaga, Jl. Tanjakan, Cimuning, Kec. Mustika Jaya, Kota Bks, Jawa Barat 17158'));
            $blueprint->add('phone', env('STORE_PHONE', '0812-3456-7890'));
            $blueprint->add('receipt_footer', env('STORE_RECEIPT_FOOTER', 'Terima kasih! Barang yang sudah dibeli tidak dapat dikembalikan.'));
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('store', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('name');
            $blueprint->delete('tagline');
            $blueprint->delete('address');
            $blueprint->delete('phone');
            $blueprint->delete('receipt_footer');
        });
    }
};
