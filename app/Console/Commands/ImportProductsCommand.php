<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class ImportProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-products {file : Path to the CSV or Excel file} {--chunk=500 : Number of products to import per phase}';

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

        $chunkSize = (int) $this->option('chunk');
        $this->info("Importing products from {$file} (Chunk size: {$chunkSize})...");

        $importClass = new class($this, $chunkSize) implements ToCollection, WithChunkReading, WithHeadingRow
        {
            private $command;

            private $chunkSize;

            public $created = 0;

            public $updated = 0;

            public function __construct($command, $chunkSize)
            {
                $this->command = $command;
                $this->chunkSize = $chunkSize;
            }

            public function collection(Collection $rows)
            {
                $this->command->info('Processing phase with '.count($rows).' rows...');
                DB::transaction(function () use ($rows) {
                    $bar = $this->command->getOutput()->createProgressBar(count($rows));
                    $bar->start();

                    foreach ($rows as $index => $row) {
                        // Support various heading names
                        $name = $row['product_name'] ?? null;
                        $categoryName = $row['category'] ?? null;

                        $price1 = $row['price_1'] ?? 0;
                        $price2 = $row['price_2'] ?? 0;
                        $resellerPrice = $row['reseller_price'] ?? 0;
                        $purchasePrice = $row['purchase_price'] ?? 0;

                        if (empty($name)) {
                            $this->command->warn('Skipping row '.($index + 2).': Product name is missing.');
                            $bar->advance();

                            continue;
                        }

                        $categoryId = null;
                        if (! empty($categoryName)) {
                            $category = Category::firstOrCreate(
                                ['name' => trim($categoryName)],
                                ['description' => 'Automatically created from import']
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
                                'purchase_price' => (float) $purchasePrice,
                            ]
                        );

                        if ($product->wasRecentlyCreated) {
                            $this->created++;
                        } else {
                            $this->updated++;
                        }

                        $bar->advance();
                    }

                    $bar->finish();
                    $this->command->newLine();
                    $this->command->newLine();
                });
            }

            public function chunkSize(): int
            {
                return $this->chunkSize;
            }
        };

        try {
            Excel::import($importClass, $file);
            $this->info("All phases completed! Total Created: {$importClass->created}, Total Updated: {$importClass->updated}");
        } catch (Exception $e) {
            $this->error('Error importing file: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
