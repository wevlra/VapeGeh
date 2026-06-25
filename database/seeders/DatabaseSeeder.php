<?php

namespace Database\Seeders;

use App\Actions\CompleteStockTransfer;
use App\Actions\CreateSale;
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
        // ── Locations ──────────────────────────────────────────────
        $warehouse = Location::factory()->warehouse()->create(['name' => 'Gudang Pusat']);
        $mainStore = Location::factory()->create(['name' => 'VapeGeh Central']);
        $branch1 = Location::factory()->create(['name' => 'VapeGeh Mall']);
        $branch2 = Location::factory()->create(['name' => 'VapeGeh Plaza']);

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
            ['name' => 'Aksesoris Vape Makmur', 'contact_person' => 'Hendra'],
        ])->map(fn ($data) => Vendor::create($data));

        // ── Products ───────────────────────────────────────────────
        $products = collect([
            ['vendor_id' => $vendors[0]->id, 'name' => 'Nasty Juice Fruity 30ml', 'purchase_price' => 35000, 'reseller_price' => 45000, 'store_price' => 55000],
            ['vendor_id' => $vendors[0]->id, 'name' => 'Nasty Juice Grape 30ml', 'purchase_price' => 35000, 'reseller_price' => 45000, 'store_price' => 55000],
            ['vendor_id' => $vendors[1]->id, 'name' => 'Crush Frozen Mint 30ml', 'purchase_price' => 35000, 'reseller_price' => 45000, 'store_price' => 55000],
            ['vendor_id' => $vendors[2]->id, 'name' => 'Zap Juice Tobacco Gold 60ml', 'purchase_price' => 60000, 'reseller_price' => 75000, 'store_price' => 95000],
            ['vendor_id' => $vendors[3]->id, 'name' => 'Pachamama Strawberry 30ml', 'purchase_price' => 40000, 'reseller_price' => 50000, 'store_price' => 65000],
            ['vendor_id' => $vendors[4]->id, 'name' => 'Voopoo PnP Coils 0.4ohm', 'purchase_price' => 40000, 'reseller_price' => 50000, 'store_price' => 65000],
            ['vendor_id' => $vendors[4]->id, 'name' => 'Voopoo PnP Coils 0.8ohm', 'purchase_price' => 40000, 'reseller_price' => 50000, 'store_price' => 65000],
            ['vendor_id' => $vendors[5]->id, 'name' => 'Uwell Caliburn Coils', 'purchase_price' => 35000, 'reseller_price' => 45000, 'store_price' => 55000],
            ['vendor_id' => $vendors[4]->id, 'name' => 'Voopoo Drag Nano 2', 'purchase_price' => 180000, 'reseller_price' => 220000, 'store_price' => 275000],
            ['vendor_id' => $vendors[5]->id, 'name' => 'Uwell Caliburn G2', 'purchase_price' => 220000, 'reseller_price' => 270000, 'store_price' => 340000],
            ['vendor_id' => $vendors[6]->id, 'name' => 'Xros 3 Mini', 'purchase_price' => 150000, 'reseller_price' => 190000, 'store_price' => 235000],
            ['vendor_id' => $vendors[7]->id, 'name' => 'USB-C Charging Cable', 'purchase_price' => 15000, 'reseller_price' => 20000, 'store_price' => 25000],
            ['vendor_id' => $vendors[7]->id, 'name' => 'Carrying Case (Small)', 'purchase_price' => 20000, 'reseller_price' => 28000, 'store_price' => 35000],
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
                $subtotal = $product->store_price * $qty;

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
            ['category' => 'sale', 'description' => 'Penjualan eceran harian', 'amount' => 275000],
            ['category' => 'sale', 'description' => 'Penjualan paket bundle', 'amount' => 450000],
            ['category' => 'sale', 'description' => 'Pembelian online via QRIS', 'amount' => 180000],
            ['category' => 'debt_payment', 'description' => 'Pembayaran hutang dari pelanggan', 'amount' => 500000],
            ['category' => 'debt_payment', 'description' => 'Pelunasan cicilan stok', 'amount' => 350000],
            ['category' => 'other', 'description' => 'Pengembalian dana supplier', 'amount' => 120000],
            ['category' => 'sale', 'description' => 'Restock selling fee', 'amount' => 200000],
            ['category' => 'sale', 'description' => 'Penjualan POD mingguan', 'amount' => 320000],
            ['category' => 'other', 'description' => 'Bonus dari distributor', 'amount' => 150000],
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
            ['category' => 'salary', 'description' => 'Gaji karyawan bulan ini', 'amount' => 4500000],
            ['category' => 'utilities', 'description' => 'Listrik dan air bulan ini', 'amount' => 750000],
            ['category' => 'utilities', 'description' => 'Internet dan WiFi', 'amount' => 350000],
            ['category' => 'transport', 'description' => 'Biaya pengiriman ke cabang', 'amount' => 150000],
            ['category' => 'transport', 'description' => 'Bensin picking ke supplier', 'amount' => 85000],
            ['category' => 'purchase', 'description' => 'Restock device Uwell Caliburn', 'amount' => 660000],
            ['category' => 'purchase', 'description' => 'Pembelian casing dan aksesoris', 'amount' => 200000],
            ['category' => 'other', 'description' => 'Pembersihan toko bulanan', 'amount' => 100000],
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
