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
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Store Settings ──────────────────────────────────────────
        /** @var \App\Settings\StoreSettings $settings */
        $settings = app(\App\Settings\StoreSettings::class);
        $settings->name = env('STORE_NAME', 'VapeGeh');
        $settings->tagline = env('STORE_TAGLINE', 'Your Vaping Partner');
        $settings->address = env('STORE_ADDRESS', 'Perum Taman Niaga, Jl. Tanjakan, Cimuning, Kec. Mustika Jaya, Kota Bks, Jawa Barat 17158');
        $settings->phone = env('STORE_PHONE', '0812-3456-7890');
        $settings->receipt_footer = env('STORE_RECEIPT_FOOTER', 'Terima kasih! Barang yang sudah dibeli tidak dapat dikembalikan.');
        $settings->save();

        // ── Locations ──────────────────────────────────────────────
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

        // ── Users ──────────────────────────────────────────────────
        $admin = User::factory()->admin()->create([
            'name' => 'Ahmad Rizky',
            'email' => 'admin@vapegeh.com',
        ]);

        $staff1 = User::factory()->create([
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@vapegeh.com',
            'role' => 'staff',
            'location_id' => $mainStore->id,
        ]);

        $staff2 = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@vapegeh.com',
            'role' => 'staff',
            'location_id' => $branch1->id,
        ]);

        $staff3 = User::factory()->create([
            'name' => 'Rina Wati',
            'email' => 'rina@vapegeh.com',
            'role' => 'staff',
            'location_id' => $branch2->id,
        ]);

        // ── Vendors ─────────────────────────────────────────────────
        $vendors = collect([
            ['name' => 'Nasty Juice Indonesia', 'contact_person' => 'Andi'],
            ['name' => 'Crush Vape Distributor', 'contact_person' => 'Bambang'],
            ['name' => 'Zap Juice Official', 'contact_person' => 'Cindy'],
            ['name' => 'Pachamama Asia', 'contact_person' => 'Dewi'],
            ['name' => 'Voopoo Indo', 'contact_person' => 'Eko'],
            ['name' => 'Uwell Distributor', 'contact_person' => 'Fajar'],
            ['name' => 'Xros Official', 'contact_person' => 'Gilang'],
            ['name' => 'Vape Accessories Makmur', 'contact_person' => 'Hendra'],
        ])->map(fn ($data) => Vendor::create($data));

        // ── Categories ───────────────────────────────────────────────
        $categories = collect([
            ['name' => 'Liquid/Juice', 'description' => 'Vape E-Liquids'],
            ['name' => 'Coils/Cartridges', 'description' => 'Replacement coils and pods'],
            ['name' => 'Devices', 'description' => 'Mods and Pod Systems'],
            ['name' => 'Accessories', 'description' => 'Batteries, chargers, cases'],
        ])->map(fn ($data) => Category::create($data));

        $catLiquid = $categories[0]->id;
        $catCoil = $categories[1]->id;
        $catDevice = $categories[2]->id;
        $catAccessory = $categories[3]->id;

        // ── Products ───────────────────────────────────────────────
        $products = collect([
            ['name' => 'Nasty Juice Fruity 30ml', 'category_id' => $catLiquid, 'purchase_price' => 35000, 'selling_price' => 55000, 'reseller_price' => 45000],
            ['name' => 'Nasty Juice Grape 30ml', 'category_id' => $catLiquid, 'purchase_price' => 35000, 'selling_price' => 55000, 'reseller_price' => 45000],
            ['name' => 'Crush Frozen Mint 30ml', 'category_id' => $catLiquid, 'purchase_price' => 35000, 'selling_price' => 55000, 'reseller_price' => 45000],
            ['name' => 'Zap Juice Tobacco Gold 60ml', 'category_id' => $catLiquid, 'purchase_price' => 60000, 'selling_price' => 95000, 'reseller_price' => 75000],
            ['name' => 'Pachamama Strawberry 30ml', 'category_id' => $catLiquid, 'purchase_price' => 40000, 'selling_price' => 65000, 'reseller_price' => 50000],
            ['name' => 'Voopoo PnP Coils 0.4ohm', 'category_id' => $catCoil, 'purchase_price' => 40000, 'selling_price' => 65000, 'reseller_price' => 50000],
            ['name' => 'Voopoo PnP Coils 0.8ohm', 'category_id' => $catCoil, 'purchase_price' => 40000, 'selling_price' => 65000, 'reseller_price' => 50000],
            ['name' => 'Uwell Caliburn Coils', 'category_id' => $catCoil, 'purchase_price' => 35000, 'selling_price' => 55000, 'reseller_price' => 45000],
            ['name' => 'Voopoo Drag Nano 2', 'category_id' => $catDevice, 'purchase_price' => 180000, 'selling_price' => 275000, 'reseller_price' => 220000],
            ['name' => 'Uwell Caliburn G2', 'category_id' => $catDevice, 'purchase_price' => 220000, 'selling_price' => 340000, 'reseller_price' => 270000],
            ['name' => 'Xros 3 Mini', 'category_id' => $catDevice, 'purchase_price' => 150000, 'selling_price' => 235000, 'reseller_price' => 190000],
            ['name' => 'USB-C Charging Cable', 'category_id' => $catAccessory, 'purchase_price' => 15000, 'selling_price' => 25000, 'reseller_price' => 20000],
            ['name' => 'Carrying Case (Small)', 'category_id' => $catAccessory, 'purchase_price' => 20000, 'selling_price' => 35000, 'reseller_price' => 28000],
        ])->map(fn ($data) => Product::create($data));

        // ── Stock at warehouse ─────────────────────────────────────
        foreach ($products as $product) {
            Stock::create([
                'product_id' => $product->id,
                'location_id' => $warehouse->id,
                'qty' => rand(80, 250),
            ]);
        }

        // ── Stock transfers (spread over past 2 weeks) ────────────
        $allStores = [$mainStore, $branch1, $branch2];
        $transferDates = [
            Carbon::now()->subDays(20),
            Carbon::now()->subDays(14),
            Carbon::now()->subDays(10),
        ];

        foreach ($allStores as $index => $store) {
            $transfer = StockTransfer::create([
                'from_location_id' => $warehouse->id,
                'to_location_id' => $store->id,
                'status' => 'pending',
                'created_by' => $admin->id,
                'created_at' => $transferDates[$index],
                'updated_at' => $transferDates[$index],
            ]);

            $items = $products->random(rand(4, 7));
            foreach ($items as $product) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'qty' => rand(10, 40),
                ]);
            }

            app(CompleteStockTransfer::class)->execute($transfer, $admin);
        }

        // ── Sales (spread over last 14 days) ──────────────────────
        $storeSales = [
            ['staff' => $staff1, 'location' => $mainStore->id, 'payment' => 'cash'],
            ['staff' => $staff1, 'location' => $mainStore->id, 'payment' => 'qris'],
            ['staff' => $staff1, 'location' => $mainStore->id, 'payment' => 'transfer'],
            ['staff' => $staff2, 'location' => $branch1->id, 'payment' => 'cash'],
            ['staff' => $staff2, 'location' => $branch1->id, 'payment' => 'qris'],
            ['staff' => $staff3, 'location' => $branch2->id, 'payment' => 'cash'],
        ];

        for ($day = 0; $day < 14; $day++) {
            $assign = $storeSales[array_rand($storeSales)];

            for ($i = 0; $i < rand(1, 3); $i++) {
                $product = $products->random();
                $qty = rand(1, 2);
                $subtotal = $product->selling_price * $qty;

                try {
                    $sale = app(CreateSale::class)->execute(
                        user: $assign['staff'],
                        locationId: $assign['location'],
                        items: [['product_id' => $product->id, 'qty' => $qty]],
                        paymentMethod: $assign['payment'],
                        paidAmount: $subtotal,
                    );

                    $sale->update([
                        'created_at' => Carbon::now()->subDays($day)->setTime(rand(8, 20), rand(0, 59)),
                        'updated_at' => Carbon::now()->subDays($day)->setTime(rand(8, 20), rand(0, 59)),
                    ]);
                } catch (\DomainException $e) {
                    // skip sale if stock insufficient
                }
            }
        }

        // ── Income entries (spread over last 30 days) ──────────────
        $incomeData = [
            ['category' => 'sale', 'description' => 'Penjualan ritel harian', 'amount' => 275000],
            ['category' => 'sale', 'description' => 'Penjualan paket bundle', 'amount' => 450000],
            ['category' => 'sale', 'description' => 'Pembelian online via QRIS', 'amount' => 180000],
            ['category' => 'debt_payment', 'description' => 'Pembayaran hutang pelanggan', 'amount' => 500000],
            ['category' => 'debt_payment', 'description' => 'Pelunasan cicilan stok', 'amount' => 350000],
            ['category' => 'opname', 'description' => 'Surplus Opname Stok Barang', 'amount' => 50000],
            ['category' => 'other', 'description' => 'Refund dari vendor', 'amount' => 120000],
            ['category' => 'sale', 'description' => 'Biaya penjualan restock', 'amount' => 200000],
            ['category' => 'sale', 'description' => 'Penjualan POD mingguan', 'amount' => 320000],
            ['category' => 'other', 'description' => 'Bonus distributor', 'amount' => 150000],
            ['category' => 'sale', 'description' => 'Penjualan coil dan cartridge', 'amount' => 285000],
        ];

        foreach ($allStores as $store) {
            foreach ($incomeData as $index => $data) {
                Income::create([
                    'location_id' => $store->id,
                    'category' => $data['category'],
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'date' => Carbon::now()->subDays(rand(0, 30)),
                    'created_by' => $admin->id,
                    'created_at' => Carbon::now()->subDays(rand(0, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(0, 30)),
                ]);
            }
        }

        // ── Expense entries (spread over last 30 days) ─────────────
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
                Expense::create([
                    'location_id' => $store->id,
                    'category' => $data['category'],
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'date' => Carbon::now()->subDays(rand(0, 30)),
                    'created_by' => $admin->id,
                    'created_at' => Carbon::now()->subDays(rand(0, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(0, 30)),
                ]);
            }
        }
    }
}
