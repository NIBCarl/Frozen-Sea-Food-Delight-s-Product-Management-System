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
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('City/Municipality name');
            $table->string('province');
            $table->string('region')->default('Visayas/Mindanao');
            $table->decimal('base_shipping_rate', 8, 2)->comment('Shipping cost in PHP');
            $table->integer('estimated_delivery_days')->comment('Estimated delivery time in days');
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_sea_transport')->default(false)->comment('Whether zone requires inter-island shipping');
            $table->text('delivery_notes')->nullable()->comment('Special delivery instructions for this zone');
            $table->timestamps();

            // Indexes for better query performance
            $table->index('province');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
