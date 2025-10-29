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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_personnel_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('scheduled_date');
            $table->datetime('actual_delivery_datetime')->nullable();
            $table->enum('status', [
                'scheduled',
                'out_for_delivery',
                'in_transit',
                'delivered',
                'failed'
            ])->default('scheduled');
            $table->text('delivery_notes')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('delivery_confirmation_photo')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('delivery_personnel_id');
            $table->index('status');
            $table->index('scheduled_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
