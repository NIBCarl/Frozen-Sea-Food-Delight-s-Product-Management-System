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
            // Update payment_method enum to include gcash
            $table->enum('payment_method', ['cash_on_delivery', 'gcash'])->default('cash_on_delivery')->change();
            
            // Add payment_status options for gcash verification
            $table->enum('payment_status', ['pending', 'paid', 'verification_pending', 'verification_failed'])->default('pending')->change();
            
            // Add payment receipt path for gcash payments
            $table->string('payment_receipt_path')->nullable()->after('payment_status');
            
            // Add payment verification timestamp
            $table->timestamp('payment_verified_at')->nullable()->after('payment_receipt_path');
            
            // Add verified by admin reference
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->after('payment_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Remove new columns
            $table->dropForeign(['payment_verified_by']);
            $table->dropColumn(['payment_receipt_path', 'payment_verified_at', 'payment_verified_by']);
            
            // Revert payment_method enum to original
            $table->enum('payment_method', ['cash_on_delivery'])->default('cash_on_delivery')->change();
            
            // Revert payment_status enum to original
            $table->enum('payment_status', ['pending', 'paid'])->default('pending')->change();
        });
    }
};
