<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->renameColumn('price', 'purchase_price');
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->renameColumn('price', 'purchase_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks_and_transfers', function (Blueprint $table) {
            //
        });
    }
};
