<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Config::set('database.connections.remote_mysql', [
    'driver' => 'mysql',
    'host' => '108.137.33.33',
    'port' => '3306',
    'database' => 'vapegeh',
    'username' => 'jagantara',
    'password' => '@Jagantara.2678',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
]);

echo "Running migrate:fresh on sqlite...\n";
\Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
echo "Migrate fresh done.\n";

$tables = DB::connection('remote_mysql')->select('SHOW TABLES');
$dbName = 'vapegeh';
$tableKey = 'Tables_in_' . $dbName;

// Order of insertion to respect foreign keys (if any exist at DB level, though sqlite default is disabled, but good practice)
// Since we use migrate:fresh, foreign keys are created. SQLite enforces them if PRAGMA foreign_keys = ON.
DB::connection('sqlite')->statement('PRAGMA foreign_keys = OFF');

foreach ($tables as $tableObj) {
    $tableName = $tableObj->$tableKey;
    if ($tableName === 'migrations') continue;

    echo "Syncing {$tableName}...\n";
    $rows = DB::connection('remote_mysql')->table($tableName)->get();
    
    $insertData = [];
    foreach ($rows as $row) {
        $rowArray = (array) $row;
        
        // Handle renaming column for stocks and stock_transfer_items
        if ($tableName === 'stocks') {
            if (array_key_exists('price', $rowArray)) {
                $rowArray['purchase_price'] = $rowArray['price'];
                unset($rowArray['price']);
            }
        }
        if ($tableName === 'stock_transfer_items') {
            if (array_key_exists('price', $rowArray)) {
                $rowArray['purchase_price'] = $rowArray['price'];
                unset($rowArray['price']);
            }
        }
        
        $insertData[] = $rowArray;
    }
    
    // Clear existing data from seeder
    DB::connection('sqlite')->table($tableName)->delete();

    // Chunk inserts
    $chunks = array_chunk($insertData, 100);
    foreach ($chunks as $chunk) {
        DB::connection('sqlite')->table($tableName)->insert($chunk);
    }
}

DB::connection('sqlite')->statement('PRAGMA foreign_keys = ON');

echo "Sync complete!\n";

// Re-run WAC recalculation
echo "Recalculating WAC...\n";
\Illuminate\Support\Facades\Artisan::call('app:recalculate-wac-prices');
echo \Illuminate\Support\Facades\Artisan::output();

// Fix admin password for testing
$admin = \App\Models\User::where('email', 'algofiqih1998@gmail.com')->first();
if ($admin) {
    $admin->password = \Illuminate\Support\Facades\Hash::make('password');
    $admin->save();
    echo "Admin password reset to 'password'.\n";
}

// Fix COGS logic on imported historical out movements (since remote mysql unit_price is 0 for outs or missing)
echo "Fixing historical COGS...\n";
$movements = DB::table('stock_movements')->where('type', 'out')->get();
foreach ($movements as $m) {
    if (!$m->unit_price || $m->unit_price == 0) {
        $stock = DB::table('stocks')->where('product_id', $m->product_id)->where('location_id', $m->location_id)->first();
        if ($stock && current($stock)) {
            // we use the current WAC for backfilling
            DB::table('stock_movements')->where('id', $m->id)->update(['unit_price' => $stock->purchase_price ?? 0]);
        }
    }
}
echo "COGS fixed.\n";
