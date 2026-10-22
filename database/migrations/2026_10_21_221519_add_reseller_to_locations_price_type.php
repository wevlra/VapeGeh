<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->enum('price_type', ['1', '2', 'reseller'])->default('1')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('locations')
            ->where('price_type', 'reseller')
            ->update(['price_type' => '1']);

        Schema::table('locations', function (Blueprint $table) {
            $table->enum('price_type', ['1', '2'])->default('1')->change();
        });
    }
};
