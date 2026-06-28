<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix cascade rules: created_by should not cascade on delete
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // Add missing indexes
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('type');
            $table->index('created_at');
            $table->index(['related_type', 'related_id']);
            $table->index('product_id');
            $table->index('location_id');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index('sale_id');
            $table->index('product_id');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->index('location_id');
            $table->index('date');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('location_id');
            $table->index('date');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('sku');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['location_id']);
            $table->dropIndex(['date']);
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropIndex(['location_id']);
            $table->dropIndex(['date']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex(['sale_id']);
            $table->dropIndex(['product_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['related_type', 'related_id']);
            $table->dropIndex(['product_id']);
            $table->dropIndex(['location_id']);
        });

        // Restore cascade rules
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
