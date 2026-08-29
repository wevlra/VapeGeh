<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('location_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['purchase', 'salary', 'utilities', 'transport', 'opname', 'other']);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
