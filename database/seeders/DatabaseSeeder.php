<?php

namespace Database\Seeders;

use App\Actions\CompleteStockTransfer;
use App\Actions\CreateSale;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Vendor;
use App\Settings\StoreSettings;
use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

use function Laravel\Prompts\multiselect;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $selected = multiselect(
            label: 'Pilih data yang ingin di-seed (Gunakan Spasi untuk memilih, Enter untuk konfirmasi):',
            options: [
                'settings' => 'Store Settings',
                'locations' => 'Locations',
                'users' => 'Users',
                'vendors' => 'Vendors',
                'categories' => 'Categories',
                'products' => 'Products',
                'transactions' => 'Dummy Transactions (Stock, Transfers, Sales, Income, Expense)',
            ],
            default: ['settings', 'locations', 'users', 'vendors', 'categories', 'products', 'transactions']
        );

        // ── Store Settings ──────────────────────────────────────────
        if (in_array('settings', $selected)) {
            /** @var StoreSettings $settings */
            $settings = app(StoreSettings::class);
            $settings->name = env('STORE_NAME', 'VapeGeh');
            $settings->tagline = env('STORE_TAGLINE', 'Your Vaping Partner');
            $settings->address = env('STORE_ADDRESS', 'Perum Taman Niaga, Jl. Tanjakan, Cimuning, Kec. Mustika Jaya, Kota Bks, Jawa Barat 17158');
            $settings->phone = env('STORE_PHONE', '0812-3456-7890');
            $settings->receipt_footer = env('STORE_RECEIPT_FOOTER', 'Terima kasih! Barang yang sudah dibeli tidak dapat dikembalikan.');
            $settings->save();
        }

        // ── Locations ──────────────────────────────────────────────
        $warehouse = null;
        $mainStore = null;
        $branch1 = null;
        $branch2 = null;
        if (in_array('locations', $selected)) {
            $warehouse = Location::factory()->warehouse()->create([
                'name' => 'Main Warehouse',
                'address' => 'Jl. Raya Bogor Km 30, RT 05/02, Cibubur, Jakarta Timur',
                'phone' => '021-87654321',
            ]);
            $mainStore = Location::factory()->create([
                'name' => 'VapeGeh Central',
                'address' => 'Jl. Sudirman Kav 52-53, SCBD, Jakarta Selatan',
                'phone' => '0812-3456-7890',
            ]);
            $branch1 = Location::factory()->create([
                'name' => 'VapeGeh Mall',
                'address' => 'Jl. Prof Dr Satrio, Kuningan, Jakarta Selatan',
                'phone' => '0813-9876-5432',
            ]);
            $branch2 = Location::factory()->create([
                'name' => 'VapeGeh Plaza',
                'address' => 'Jl. M.H Thamrin, Menteng, Jakarta Pusat',
                'phone' => '0856-1234-5678',
            ]);
        }

        // ── Users ──────────────────────────────────────────────────
        $admin = null;
        $staff1 = null;
        $staff2 = null;
        $staff3 = null;
        if (in_array('users', $selected)) {
            $mainStoreId = $mainStore?->id ?? Location::where('name', 'VapeGeh Central')->value('id');
            $branch1Id = $branch1?->id ?? Location::where('name', 'VapeGeh Mall')->value('id');
            $branch2Id = $branch2?->id ?? Location::where('name', 'VapeGeh Plaza')->value('id');

            $admin = User::factory()->admin()->create([
                'name' => 'Ahmad Rizky',
                'email' => 'admin@vapegeh.com',
            ]);

            $staff1 = User::factory()->create([
                'name' => 'Siti Nurhaliza',
                'email' => 'siti@vapegeh.com',
                'role' => 'staff',
                'location_id' => $mainStoreId,
            ]);

            $staff2 = User::factory()->create([
                'name' => 'Budi Santoso',
                'email' => 'budi@vapegeh.com',
                'role' => 'staff',
                'location_id' => $branch1Id,
            ]);

            $staff3 = User::factory()->create([
                'name' => 'Rina Wati',
                'email' => 'rina@vapegeh.com',
                'role' => 'staff',
                'location_id' => $branch2Id,
            ]);
        }

        // ── Vendors ─────────────────────────────────────────────────
        if (in_array('vendors', $selected)) {
            collect([
                ['name' => 'Nasty Juice Indonesia', 'contact_person' => 'Andi'],
                ['name' => 'Crush Vape Distributor', 'contact_person' => 'Bambang'],
                ['name' => 'Zap Juice Official', 'contact_person' => 'Cindy'],
                ['name' => 'Pachamama Asia', 'contact_person' => 'Dewi'],
                ['name' => 'Voopoo Indo', 'contact_person' => 'Eko'],
                ['name' => 'Uwell Distributor', 'contact_person' => 'Fajar'],
                ['name' => 'Xros Official', 'contact_person' => 'Gilang'],
                ['name' => 'Vape Accessories Makmur', 'contact_person' => 'Hendra'],
            ])->each(fn ($data) => Vendor::create($data));
        }

        // ── Categories ───────────────────────────────────────────────
        $categories = collect();
        if (in_array('categories', $selected)) {
            $categories = collect([
                ['name' => 'Liquid/Juice', 'description' => 'Vape E-Liquids'],
                ['name' => 'Coils/Cartridges', 'description' => 'Replacement coils and pods'],
                ['name' => 'Devices', 'description' => 'Mods and Pod Systems'],
                ['name' => 'Accessories', 'description' => 'Batteries, chargers, cases'],
            ])->map(fn ($data) => Category::create($data));
        }

        // ── Products ───────────────────────────────────────────────
        $products = collect();
        if (in_array('products', $selected)) {
            $catLiquid = $categories->firstWhere('name', 'Liquid/Juice')?->id ?? Category::where('name', 'Liquid/Juice')->value('id');
            $catCoil = $categories->firstWhere('name', 'Coils/Cartridges')?->id ?? Category::where('name', 'Coils/Cartridges')->value('id');
            $catDevice = $categories->firstWhere('name', 'Devices')?->id ?? Category::where('name', 'Devices')->value('id');
            $catAccessory = $categories->firstWhere('name', 'Accessories')?->id ?? Category::where('name', 'Accessories')->value('id');

            $products = collect([
                ['name' => 'Nasty Juice Fruity 30ml', 'category_id' => $catLiquid, 'purchase_price' => 35000, 'price_1' => 55000, 'reseller_price' => 45000, 'price_2' => 50000],
                ['name' => 'Nasty Juice Grape 30ml', 'category_id' => $catLiquid, 'purchase_price' => 35000, 'price_1' => 55000, 'reseller_price' => 45000, 'price_2' => 50000],
                ['name' => 'Crush Frozen Mint 30ml', 'category_id' => $catLiquid, 'purchase_price' => 35000, 'price_1' => 55000, 'reseller_price' => 45000, 'price_2' => 50000],
                ['name' => 'Zap Juice Tobacco Gold 60ml', 'category_id' => $catLiquid, 'purchase_price' => 60000, 'price_1' => 95000, 'reseller_price' => 75000, 'price_2' => 85000],
                ['name' => 'Pachamama Strawberry 30ml', 'category_id' => $catLiquid, 'purchase_price' => 40000, 'price_1' => 65000, 'reseller_price' => 50000, 'price_2' => 60000],
                ['name' => 'Voopoo PnP Coils 0.4ohm', 'category_id' => $catCoil, 'purchase_price' => 40000, 'price_1' => 65000, 'reseller_price' => 50000, 'price_2' => 60000],
                ['name' => 'Voopoo PnP Coils 0.8ohm', 'category_id' => $catCoil, 'purchase_price' => 40000, 'price_1' => 65000, 'reseller_price' => 50000, 'price_2' => 60000],
                ['name' => 'Uwell Caliburn Coils', 'category_id' => $catCoil, 'purchase_price' => 35000, 'price_1' => 55000, 'reseller_price' => 45000, 'price_2' => 50000],
                ['name' => 'Voopoo Drag Nano 2', 'category_id' => $catDevice, 'purchase_price' => 180000, 'price_1' => 275000, 'reseller_price' => 220000, 'price_2' => 250000],
                ['name' => 'Uwell Caliburn G2', 'category_id' => $catDevice, 'purchase_price' => 220000, 'price_1' => 340000, 'reseller_price' => 270000, 'price_2' => 300000],
                ['name' => 'Xros 3 Mini', 'category_id' => $catDevice, 'purchase_price' => 150000, 'price_1' => 235000, 'reseller_price' => 190000, 'price_2' => 210000],
                ['name' => 'USB-C Charging Cable', 'category_id' => $catAccessory, 'purchase_price' => 15000, 'price_1' => 25000, 'reseller_price' => 20000, 'price_2' => 22000],
                ['name' => 'Carrying Case (Small)', 'category_id' => $catAccessory, 'purchase_price' => 20000, 'price_1' => 35000, 'reseller_price' => 28000, 'price_2' => 30000],
            ])->map(fn ($data) => Product::create($data));

            $availableLocations = Location::all();
            if ($availableLocations->isEmpty()) {
                $availableLocations->push(Location::factory()->warehouse()->create([
                    'name' => 'Main Warehouse',
                ]));
            }

            foreach ($products as $product) {
                foreach ($availableLocations as $loc) {
                    $stock = Stock::firstOrCreate([
                        'product_id' => $product->id,
                        'location_id' => $loc->id,
                    ], [
                        'qty' => rand(10, 30),
                    ]);

                    if ($stock->wasRecentlyCreated) {
                        StockMovement::create([
                            'product_id' => $product->id,
                            'location_id' => $loc->id,
                            'type' => 'in',
                            'quantity' => $stock->qty,
                            'unit_price' => $product->purchase_price,
                            'notes' => 'Stok Awal (Seeder)',
                        ]);
                    }
                }
            }
        }

        // ── Transactions ───────────────────────────────────────────
        if (in_array('transactions', $selected)) {
            $now = Carbon::now();

            $warehouse = $warehouse ?? Location::where('name', 'Main Warehouse')->first();
            $mainStore = $mainStore ?? Location::where('name', 'VapeGeh Central')->first();
            $branch1 = $branch1 ?? Location::where('name', 'VapeGeh Mall')->first();
            $branch2 = $branch2 ?? Location::where('name', 'VapeGeh Plaza')->first();

            $admin = $admin ?? User::where('email', 'admin@vapegeh.com')->first();
            $staff1 = $staff1 ?? User::where('email', 'siti@vapegeh.com')->first();
            $staff2 = $staff2 ?? User::where('email', 'budi@vapegeh.com')->first();
            $staff3 = $staff3 ?? User::where('email', 'rina@vapegeh.com')->first();

            $products = $products->isEmpty() ? Product::all() : $products;

            if ($warehouse && $products->isNotEmpty()) {
                // ── Stock transfers
                $allStores = array_filter([$mainStore, $branch1, $branch2]);
                $transferDates = [
                    $now->copy()->subDays(20)->setTime(10, 0, 0),
                    $now->copy()->subDays(14)->setTime(11, 30, 0),
                    $now->copy()->subDays(10)->setTime(14, 15, 0),
                ];

                foreach ($allStores as $index => $store) {
                    if (! isset($transferDates[$index])) {
                        continue;
                    }

                    $transfer = StockTransfer::create([
                        'from_location_id' => $warehouse->id,
                        'to_location_id' => $store->id,
                        'status' => 'pending',
                        'created_by' => $admin?->id,
                        'created_at' => $transferDates[$index],
                        'updated_at' => $transferDates[$index],
                    ]);

                    $items = $products->random(min(rand(4, 7), $products->count()));
                    foreach ($items as $product) {
                        StockTransferItem::create([
                            'stock_transfer_id' => $transfer->id,
                            'product_id' => $product->id,
                            'qty' => rand(2, 5),
                        ]);
                    }

                    if ($admin) {
                        app(CompleteStockTransfer::class)->execute($transfer, $admin, $transferDates[$index]);
                    }
                }

                // ── Sales
                if ($staff1 && $staff2 && $staff3) {
                    $storeSales = [
                        ['staff' => $staff1, 'location' => $mainStore?->id, 'payment' => 'cash'],
                        ['staff' => $staff1, 'location' => $mainStore?->id, 'payment' => 'qris'],
                        ['staff' => $staff1, 'location' => $mainStore?->id, 'payment' => 'transfer'],
                        ['staff' => $staff2, 'location' => $branch1?->id, 'payment' => 'cash'],
                        ['staff' => $staff2, 'location' => $branch1?->id, 'payment' => 'qris'],
                        ['staff' => $staff3, 'location' => $branch2?->id, 'payment' => 'cash'],
                    ];

                    for ($day = 0; $day < 14; $day++) {
                        $assign = $storeSales[array_rand($storeSales)];
                        if (! $assign['location']) {
                            continue;
                        }

                        for ($i = 0; $i < rand(1, 3); $i++) {
                            $product = $products->random();
                            $qty = rand(1, 2);
                            $subtotal = $product->price_1 * $qty;

                            $saleDate = $now->copy()->subDays($day);
                            if ($day === 0) {
                                $startHour = 8;
                                $currentHour = (int) $now->format('H');
                                if ($currentHour > $startHour) {
                                    $hour = rand($startHour, $currentHour - 1);
                                    $minute = rand(0, 59);
                                } else {
                                    $hour = (int) $now->format('H');
                                    $minute = rand(0, max(0, (int) $now->format('i') - 1));
                                }
                                $saleDate = $saleDate->setTime($hour, $minute, rand(0, 59));
                            } else {
                                $saleDate = $saleDate->setTime(rand(8, 20), rand(0, 59));
                            }

                            if ($saleDate->isAfter($now)) {
                                $saleDate = $now->copy()->subMinutes(rand(1, 60));
                            }

                            try {
                                app(CreateSale::class)->execute(
                                    user: $assign['staff'],
                                    locationId: $assign['location'],
                                    items: [['product_id' => $product->id, 'qty' => $qty]],
                                    paymentMethod: $assign['payment'],
                                    paidAmount: $subtotal,
                                    createdAt: $saleDate,
                                );
                            } catch (DomainException $e) {
                                // skip sale if stock insufficient
                            }
                        }
                    }
                }

                // ── Income entries
                $incomeData = [
                    ['category' => 'sale', 'description' => 'Penjualan ritel harian', 'amount' => 275000],
                    ['category' => 'sale', 'description' => 'Penjualan paket bundle', 'amount' => 450000],
                    ['category' => 'sale', 'description' => 'Pembelian online via QRIS', 'amount' => 180000],
                    ['category' => 'debt_payment', 'description' => 'Pembayaran hutang pelanggan', 'amount' => 500000],
                    ['category' => 'debt_payment', 'description' => 'Pelunasan cicilan stok', 'amount' => 350000],
                    ['category' => 'service', 'description' => 'Jasa coil master / wicking service', 'amount' => 75000],
                    ['category' => 'cashback', 'description' => 'Cashback promo e-wallet', 'amount' => 120000],
                    ['category' => 'investment', 'description' => 'Suntikan modal tambahan usaha', 'amount' => 1000000],
                    ['category' => 'opname', 'description' => 'Surplus Opname Stok Barang', 'amount' => 50000],
                    ['category' => 'other', 'description' => 'Refund biaya sampel dari vendor', 'amount' => 120000],
                    ['category' => 'sale', 'description' => 'Penjualan POD mingguan', 'amount' => 320000],
                    ['category' => 'other', 'description' => 'Bonus distributor supplier', 'amount' => 150000],
                    ['category' => 'sale', 'description' => 'Penjualan coil dan cartridge', 'amount' => 285000],
                ];

                foreach ($allStores as $store) {
                    foreach ($incomeData as $index => $data) {
                        $daysAgo = rand(0, 30);
                        $incomeDate = $now->copy()->subDays($daysAgo);
                        if ($daysAgo === 0) {
                            $incomeDate = $now->copy()->subMinutes(rand(5, 300));
                        } else {
                            $incomeDate = $incomeDate->setTime(rand(8, 20), rand(0, 59));
                        }

                        if ($incomeDate->isAfter($now)) {
                            $incomeDate = $now->copy()->subMinutes(rand(1, 60));
                        }

                        Income::create([
                            'location_id' => $store->id,
                            'category' => $data['category'],
                            'description' => $data['description'],
                            'amount' => $data['amount'],
                            'date' => $incomeDate->format('Y-m-d'),
                            'created_by' => $admin?->id,
                            'created_at' => $incomeDate,
                            'updated_at' => $incomeDate,
                        ]);
                    }
                }

                // ── Expense entries
                $expenseData = [
                    ['category' => 'purchase', 'description' => 'Restock liquid Nasty Juice 30ml', 'amount' => 350000],
                    ['category' => 'purchase', 'description' => 'Restock coil Voopoo PnP', 'amount' => 400000],
                    ['category' => 'salary', 'description' => 'Gaji karyawan bulanan', 'amount' => 4500000],
                    ['category' => 'utilities', 'description' => 'Listrik dan air bulanan', 'amount' => 750000],
                    ['category' => 'utilities', 'description' => 'Internet dan WiFi', 'amount' => 350000],
                    ['category' => 'opname', 'description' => 'Penyusutan / Selisih Opname', 'amount' => 125000],
                    ['category' => 'transport', 'description' => 'Ongkos kirim ke cabang', 'amount' => 150000],
                    ['category' => 'transport', 'description' => 'Bensin jemput barang', 'amount' => 85000],
                    ['category' => 'purchase', 'description' => 'Restock device Uwell Caliburn', 'amount' => 660000],
                    ['category' => 'purchase', 'description' => 'Pembelian casing dan aksesoris', 'amount' => 200000],
                    ['category' => 'other', 'description' => 'Kebersihan toko bulanan', 'amount' => 100000],
                    ['category' => 'purchase', 'description' => 'Restock liquid Pachamama', 'amount' => 400000],
                    ['category' => 'utilities', 'description' => 'Biaya parkir karyawan', 'amount' => 120000],
                ];

                foreach ($allStores as $store) {
                    foreach ($expenseData as $index => $data) {
                        $daysAgo = rand(0, 30);
                        $expenseDate = $now->copy()->subDays($daysAgo);
                        if ($daysAgo === 0) {
                            $expenseDate = $now->copy()->subMinutes(rand(5, 300));
                        } else {
                            $expenseDate = $expenseDate->setTime(rand(8, 20), rand(0, 59));
                        }

                        if ($expenseDate->isAfter($now)) {
                            $expenseDate = $now->copy()->subMinutes(rand(1, 60));
                        }

                        Expense::create([
                            'location_id' => $store->id,
                            'category' => $data['category'],
                            'description' => $data['description'],
                            'amount' => $data['amount'],
                            'date' => $expenseDate->format('Y-m-d'),
                            'created_by' => $admin?->id,
                            'created_at' => $expenseDate,
                            'updated_at' => $expenseDate,
                        ]);
                    }
                }
            }
        }
    }
}
