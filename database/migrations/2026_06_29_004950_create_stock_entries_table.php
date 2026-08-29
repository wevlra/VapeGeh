<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // 'in' or 'out'
            $table->foreignUuid('location_id')->constrained();
            $table->foreignUuid('vendor_id')->nullable()->constrained();
            $table->foreignUuid('buyer_id')->nullable()->constrained();
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->json('additional_costs')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_entry_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained();
            $table->integer('qty');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_entry_items');
        Schema::dropIfExists('stock_entries');
    }
};
