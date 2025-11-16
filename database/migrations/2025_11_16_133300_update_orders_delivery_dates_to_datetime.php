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
        Schema::table('orders', function (Blueprint $table) {
            // Change date fields to datetime to include time
            $table->datetime('preferred_delivery_date')->nullable()->change();
            $table->datetime('actual_delivery_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert datetime fields back to date
            $table->date('preferred_delivery_date')->nullable()->change();
            $table->date('actual_delivery_date')->nullable()->change();
        });
    }
};
