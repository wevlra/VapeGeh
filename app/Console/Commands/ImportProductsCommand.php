<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class ImportProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-products {file : Path to the CSV or Excel file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from a CSV/Excel file (columns: product_name, category, price_1, price_2, reseller_price)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return Command::FAILURE;
        }

        $this->info("Importing products from {$file}...");

        $importClass = new class($this) implements ToCollection, WithHeadingRow
        {
            private $command;

            public function __construct($command)
            {
                $this->command = $command;
            }

            public function collection(Collection $rows)
            {
                DB::transaction(function () use ($rows) {
                    $created = 0;
                    $updated = 0;

                    foreach ($rows as $index => $row) {
                        // Support various heading names
                        $name = $row['product_name'] ?? null;
                        $categoryName = $row['category'] ?? null;

                        $price1 = $row['price_1'] ?? $row['price_1'] ?? 0;
                        $price2 = $row['price_2'] ?? $row['price_2'] ?? 0;
                        $resellerPrice = $row['reseller_price'] ?? 0;

                        if (empty($name)) {
                            $this->command->warn('Skipping row '.($index + 2).': Product name is missing.');

                            continue;
                        }

                        $categoryId = null;
                        if (! empty($categoryName)) {
                            $category = Category::firstOrCreate(
                                ['name' => trim($categoryName)],
                                ['description' => 'Dibuat otomatis dari import']
                            );
                            $categoryId = $category->id;
                        }

                        $product = Product::updateOrCreate(
                            ['name' => trim($name)],
                            [
                                'category_id' => $categoryId,
                                'price_1' => (float) $price1,
                                'price_2' => (float) $price2,
                                'reseller_price' => (float) $resellerPrice,
                                'purchase_price' => 0, // default if not provided
                            ]
                        );

                        if ($product->wasRecentlyCreated) {
                            $created++;
                        } else {
                            $updated++;
                        }
                    }

                    $this->command->info("Import selesai! Dibuat: {$created}, Diupdate: {$updated}");
                });
            }
        };

        try {
            Excel::import($importClass, $file);
        } catch (\Exception $e) {
            $this->error('Error importing file: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
