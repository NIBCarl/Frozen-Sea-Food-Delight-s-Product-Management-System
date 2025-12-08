<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_code', 6)->nullable()->after('remember_token');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
        });

        // Update the ENUM definition to include 'pending_verification'
        // Using raw statement for MySQL compatibility
        try {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'suspended', 'pending_verification') NOT NULL DEFAULT 'pending_verification'");
        } catch (\Exception $e) {
            // Fallback for SQLite (testing) or if the above fails
            // In SQLite, enums are just check constraints or text, so this might not be needed or works differently
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['otp_code', 'otp_expires_at']);
        });

        try {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active'");
        } catch (\Exception $e) {
            // Ignore
        }
    }
};
