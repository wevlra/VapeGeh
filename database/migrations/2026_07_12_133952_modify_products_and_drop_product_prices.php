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
        Schema::dropIfExists('product_prices');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('selling_price');
            $table->decimal('reseller_price', 15, 2)->default(0)->after('purchase_price');
            $table->decimal('price_1', 15, 2)->default(0)->after('reseller_price');
            $table->decimal('price_2', 15, 2)->default(0)->after('price_1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['reseller_price', 'price_1', 'price_2']);
            $table->decimal('selling_price', 12, 2)->default(0)->after('purchase_price');
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->decimal('price', 15, 2)->default(0);
            $table->timestamps();
        });
    }
};
