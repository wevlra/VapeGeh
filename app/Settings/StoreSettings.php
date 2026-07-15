<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class StoreSettings extends Settings
{
    public string $name = 'VapeGeh';

    public string $tagline = 'Your Vaping Partner';

    public string $address = 'Perum Taman Niaga, Jl. Tanjakan, Cimuning, Kec. Mustika Jaya, Kota Bks, Jawa Barat 17158';

    public string $phone = '0812-3456-7890';

    public string $receipt_footer = 'Terima kasih! Barang yang sudah dibeli tidak dapat dikembalikan.';

    public static function group(): string
    {
        return 'store';
    }
}
