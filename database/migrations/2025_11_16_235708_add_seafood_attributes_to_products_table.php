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
        Schema::table('products', function (Blueprint $table) {
            // Seafood-specific attributes (excluding expiration_date - already exists)
            $table->date('catch_date')->nullable()->after('description')->comment('Date when fish was caught');
            $table->string('storage_temperature', 20)->default('-18°C')->comment('Required storage temperature');
            $table->string('fishing_method', 100)->nullable()->comment('Method used to catch fish (e.g., Trawling, Line Fishing)');
            $table->string('origin_waters', 100)->default('Cebu Waters')->comment('Waters where fish was caught');
            $table->date('processing_date')->nullable()->comment('Date when fish was processed/cleaned');
            $table->boolean('is_frozen')->default(true)->comment('Whether product is frozen');
            $table->string('fish_type', 100)->nullable()->comment('Type of fish (e.g., Tuna, Bangus, Squid)');
            $table->decimal('weight_kg', 8, 2)->nullable()->comment('Product weight in kilograms');
            $table->enum('freshness_grade', ['A', 'B', 'C'])->default('A')->comment('Quality/freshness grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop seafood-specific columns (excluding expiration_date - managed by older migration)
            $table->dropColumn([
                'catch_date',
                'storage_temperature',
                'fishing_method',
                'origin_waters',
                'processing_date',
                'is_frozen',
                'fish_type',
                'weight_kg',
                'freshness_grade'
            ]);
        });
    }
};
