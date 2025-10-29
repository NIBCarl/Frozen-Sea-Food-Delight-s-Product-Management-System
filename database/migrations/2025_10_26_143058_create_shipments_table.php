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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number')->unique();
            $table->foreignId('supplier_id')->constrained('users')->onDelete('cascade');
            $table->date('expected_arrival_date');
            $table->date('actual_arrival_date')->nullable();
            $table->enum('status', ['pending', 'in_transit', 'arrived', 'confirmed'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('shipping_documents')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            
            $table->index('supplier_id');
            $table->index('status');
            $table->index('shipment_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
